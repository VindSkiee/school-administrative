<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Principal;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubjectCompetencySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminSemesterReportService
{
    public function __construct(protected ReportPdfService $pdfService) {}

    /**
     * Ambil status kesiapan rapor satu siswa pada tahun ajaran tertentu.
     *
     * @return array{student_id:int,nis:string,name:string,class_name:string,is_ready:bool,missing_info:string}
     */
    public function getStudentReadiness(int $academicYearId, int $studentId): array
    {
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);
        $student = Student::query()->with('user')->findOrFail($studentId);
        $schoolClass = $this->findStudentClassForAcademicYear($academicYear, $student);

        if (! $schoolClass) {
            return [
                'student_id' => $student->user_id,
                'nis' => $student->nis ?? '-',
                'nisn' => $student->nisn ?? '-',
                'name' => $student->user?->name ?? '-',
                'class_id' => null,
                'class_name' => '-',
                'is_ready' => false,
                'missing_info' => 'Siswa belum terdaftar di kelas pada tahun ajaran ini',
            ];
        }

        return $this->buildStudentReadinessPayload($schoolClass, $student);
    }

    /**
     * Ambil status kesiapan rapor seluruh siswa pada tahun ajaran tertentu.
     *
     * @return array{is_all_ready:bool,data:array<int, array{student_id:int,nis:string,name:string,class_name:string,is_ready:bool,missing_info:string}>}
     */
    // PERF FIX: replaced massive eager load with raw SQL computation at DB level
    public function getAcademicYearReadiness(int $academicYearId): array
    {
        return Cache::remember("report_distribution_{$academicYearId}", 300, function () use ($academicYearId) {
            // PERF FIX: Single raw SQL that computes everything at DB level
            // instead of loading all eager relations into memory
            $rows = DB::select('
                SELECT
                    u.id AS user_id,
                    s.nis,
                    s.nisn,
                    u.name,
                    sc.id AS class_id,
                    sc.name AS class_name,
                    -- Check if student has attendance for ALL schedules in their class
                    CASE WHEN (
                        SELECT COUNT(DISTINCT sch2.id)
                        FROM schedules sch2
                        WHERE sch2.class_id = sc.id
                        AND sch2.academic_year_id = ?
                    ) = 0 THEN 1
                    WHEN (
                        SELECT COUNT(DISTINCT a.id)
                        FROM attendances a
                        INNER JOIN schedules sch3 ON sch3.id = a.schedule_id
                        WHERE sch3.class_id = sc.id
                        AND sch3.academic_year_id = ?
                        AND a.student_id = u.id
                    ) >= (
                        SELECT COUNT(DISTINCT sch4.id)
                        FROM schedules sch4
                        WHERE sch4.class_id = sc.id
                        AND sch4.academic_year_id = ?
                    ) THEN 1
                    ELSE 0
                    END AS attendance_ready,
                    -- Check if all assignments are graded
                    CASE WHEN (
                        SELECT COUNT(DISTINCT a2.id)
                        FROM assignments a2
                        INNER JOIN schedules sch5 ON sch5.id = a2.schedule_id
                        WHERE sch5.class_id = sc.id
                        AND sch5.academic_year_id = ?
                    ) = 0 THEN 1
                    WHEN (
                        SELECT COUNT(DISTINCT g.id)
                        FROM grades g
                        INNER JOIN submissions sub ON sub.id = g.submission_id
                        INNER JOIN assignments a3 ON a3.id = sub.assignment_id
                        INNER JOIN schedules sch6 ON sch6.id = a3.schedule_id
                        WHERE sch6.class_id = sc.id
                        AND sch6.academic_year_id = ?
                        AND sub.student_id = u.id
                    ) >= (
                        SELECT COUNT(DISTINCT a4.id)
                        FROM assignments a4
                        INNER JOIN schedules sch7 ON sch7.id = a4.schedule_id
                        WHERE sch7.class_id = sc.id
                        AND sch7.academic_year_id = ?
                    ) THEN 1
                    ELSE 0
                    END AS grades_ready,
                    -- Check if all subjects in this class have competency settings configured
                    CASE WHEN (
                        SELECT COUNT(DISTINCT sch8.subject_id)
                        FROM schedules sch8
                        WHERE sch8.class_id = sc.id
                        AND sch8.academic_year_id = ?
                    ) = 0 THEN 1
                    WHEN (
                        SELECT COUNT(DISTINCT scs.subject_id)
                        FROM subject_competency_settings scs
                        INNER JOIN schedules sch9 ON sch9.subject_id = scs.subject_id
                        WHERE sch9.class_id = sc.id
                        AND sch9.academic_year_id = ?
                        AND scs.academic_year_id = ?
                    ) >= (
                        SELECT COUNT(DISTINCT sch10.subject_id)
                        FROM schedules sch10
                        WHERE sch10.class_id = sc.id
                        AND sch10.academic_year_id = ?
                    ) THEN 1
                    ELSE 0
                    END AS competency_ready
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                INNER JOIN class_student cs ON cs.student_id = u.id
                INNER JOIN classes sc ON sc.id = cs.class_id
                WHERE sc.academic_year_id = ?
                ORDER BY sc.name, u.name
            ', [$academicYearId, $academicYearId, $academicYearId, $academicYearId, $academicYearId, $academicYearId, $academicYearId, $academicYearId, $academicYearId, $academicYearId, $academicYearId]);

            $data = [];
            foreach ($rows as $row) {
                $isReady = (bool) $row->attendance_ready && (bool) $row->grades_ready && (bool) $row->competency_ready;
                $missingParts = [];

                if (! $row->attendance_ready) {
                    $missingParts[] = 'Kehadiran belum direkap';
                }
                if (! $row->grades_ready) {
                    $missingParts[] = 'Nilai belum diisi';
                }
                if (! $row->competency_ready) {
                    $missingParts[] = 'Capaian kompetensi belum dikonfigurasi untuk semua mapel';
                }

                $data[] = [
                    'student_id' => $row->user_id,
                    'nis' => $row->nis ?? '-',
                    'nisn' => $row->nisn ?? '-',
                    'name' => $row->name ?? '-',
                    'class_id' => $row->class_id,
                    'class_name' => $row->class_name,
                    'is_ready' => $isReady,
                    'missing_info' => implode('; ', $missingParts),
                ];
            }

            return [
                'is_all_ready' => empty($data) || collect($data)->every(fn (array $studentReadiness): bool => $studentReadiness['is_ready']),
                'data' => $data,
            ];
        });
    }

    public function publishReport(int $academicYearId): AcademicYear
    {
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);

        if ($academicYear->is_report_published) {
            throw new HttpException(422, 'Rapor untuk semester ini sudah diterbitkan sebelumnya.');
        }

        $academicYear->is_report_published = true;
        $academicYear->save();

        return $academicYear;
    }

    public function downloadStudentPdf(int $academicYearId, int $studentId)
    {
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);
        $student = Student::query()->with('user')->findOrFail($studentId);
        $schoolClass = $this->findStudentClassForAcademicYear($academicYear, $student);

        if (! $schoolClass) {
            $this->throwIncompleteReportException([
                'attendance' => true,
                'incomplete_subjects' => [],
            ]);
        }

        // Validate competency settings for all subjects in this class
        $subjectIds = $schoolClass->schedules->pluck('subject_id')->unique()->filter()->values();
        $configuredSubjectIds = SubjectCompetencySetting::where('academic_year_id', $academicYearId)
            ->whereIn('subject_id', $subjectIds)
            ->pluck('subject_id')
            ->toArray();
        $missingCompetency = $subjectIds->diff($configuredSubjectIds);

        if ($missingCompetency->isNotEmpty()) {
            $missingNames = $schoolClass->schedules
                ->filter(fn ($s) => $missingCompetency->contains($s->subject_id))
                ->pluck('subject.name')
                ->unique()
                ->values()
                ->implode(', ');

            throw new HttpException(422, "Capaian kompetensi belum dikonfigurasi untuk mapel: {$missingNames}. Silakan atur di menu Mata Pelajaran → Detail → Capaian Kompetensi.");
        }

        $reportData = $this->buildReportData($academicYear, $student, $schoolClass);

        return $this->pdfService->generateSemesterReportPdf($reportData, $student->user->name);
    }

    private function findStudentClassForAcademicYear(AcademicYear $academicYear, Student $student): ?SchoolClass
    {
        return $student->classes()
            ->where('classes.academic_year_id', $academicYear->id)
            ->with([
                'academicYear',
                'homeroomTeacher.user',
                'schedules' => function ($scheduleQuery) use ($academicYear): void {
                    $scheduleQuery->where('academic_year_id', $academicYear->id)
                        ->with([
                            'subject',
                            'teacher.user',
                            'attendances',
                            'assignments.submissions.grade',
                        ]);
                },
            ])
            ->first();
    }

    /**
     * @return array{student_id:int,nis:string,name:string,class_name:string,is_ready:bool,missing_info:string}
     */
    private function buildStudentReadinessPayload(SchoolClass $schoolClass, Student $student): array
    {
        $missingSubjects = [];
        $attendanceMissing = $schoolClass->schedules->isEmpty();

        // Check competency settings for all subjects in this class
        $subjectIds = $schoolClass->schedules->pluck('subject_id')->unique()->filter()->values();
        $configuredSubjectIds = SubjectCompetencySetting::where('academic_year_id', $schoolClass->academic_year_id)
            ->whereIn('subject_id', $subjectIds)
            ->pluck('subject_id')
            ->toArray();
        $missingCompetencySubjects = $subjectIds->diff($configuredSubjectIds);
        $allCompetencyConfigured = $missingCompetencySubjects->isEmpty();

        foreach ($schoolClass->schedules as $schedule) {
            if ($schedule->attendances->where('student_id', $student->user_id)->isEmpty()) {
                $attendanceMissing = true;
            }

            $subject = $schedule->subject;
            $assignmentCount = $schedule->assignments->count();
            $gradedAssignmentCount = 0;

            foreach ($schedule->assignments as $assignment) {
                $submission = $assignment->submissions->firstWhere('student_id', $student->user_id);

                if ($submission?->grade?->score !== null) {
                    $gradedAssignmentCount++;
                }
            }

            if ($assignmentCount === 0 || $gradedAssignmentCount < $assignmentCount) {
                $missingSubjects[] = $subject?->name ?? $subject?->code ?? '-';
            }
        }

        $missingInfoParts = [];

        if (! empty($missingSubjects)) {
            $missingInfoParts[] = 'Nilai '.implode(', ', array_values(array_unique($missingSubjects))).' belum diisi';
        }

        if ($attendanceMissing) {
            $missingInfoParts[] = 'Kehadiran belum direkap';
        }

        if (! $allCompetencyConfigured) {
            $missingInfoParts[] = 'Capaian kompetensi belum dikonfigurasi untuk semua mapel';
        }

        return [
            'student_id' => $student->user_id,
            'nis' => $student->nis ?? '-',
            'nisn' => $student->nisn ?? '-',
            'name' => $student->user?->name ?? '-',
            'class_id' => $schoolClass->id,
            'class_name' => $schoolClass->name,
            'is_ready' => empty($missingInfoParts),
            'missing_info' => implode('; ', $missingInfoParts),
        ];
    }

    /**
     * Public wrapper for building report data (used by StudentReportController).
     */
    public function buildReportDataPublic(AcademicYear $academicYear, Student $student, SchoolClass $schoolClass): array
    {
        return $this->buildReportData($academicYear, $student, $schoolClass);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(AcademicYear $academicYear, Student $student, SchoolClass $schoolClass): array
    {
        $principal = Principal::query()->with('user')->first();

        return [
            'school_name' => config('app.school_name', 'SMP NEGERI 5 PURWAKARTA'),
            'school_address' => config('app.school_address', 'Jl. Kolonel Singawinata No. 97 Purwakarta'),
            'academic_year' => $academicYear->name,
            'semester' => $academicYear->semester,
            'semester_label' => $academicYear->semester === 'odd' ? 'Ganjil' : 'Genap',
            'student_name' => $student->user?->name ?? '-',
            'student_nis' => $student->nis ?? '-',
            'student_nisn' => $student->nisn ?? '-',
            'class_name' => $schoolClass->name,
            'homeroom_teacher_name' => $schoolClass->homeroomTeacher?->user?->name ?? '-',
            'homeroom_teacher_nip' => $schoolClass->homeroomTeacher?->nip ?? '-',
            'principal_name' => $principal?->user?->name ?? '-',
            'principal_nip' => $principal?->nip ?? '-',
            'generated_at' => now()->format('d-m-Y'),
            'homeroom_note' => '-',
            'results' => $this->buildSubjectResults($schoolClass, $student, $academicYear),
            'attendance' => $this->buildAttendanceSummary($schoolClass, $student),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSubjectResults(SchoolClass $schoolClass, Student $student, AcademicYear $academicYear): array
    {
        // Preload all competency settings for this academic year
        $competencySettings = SubjectCompetencySetting::where('academic_year_id', $academicYear->id)
            ->get()
            ->keyBy('subject_id');

        return $schoolClass->schedules
            ->map(function ($schedule) use ($student, $competencySettings): array {
                $scores = collect();

                foreach ($schedule->assignments as $assignment) {
                    $submission = $assignment->submissions->firstWhere('student_id', $student->user_id);

                    if ($submission?->grade?->score !== null) {
                        $scores->push((float) $submission->grade->score);
                    }
                }

                $finalScore = $scores->isNotEmpty() ? round($scores->avg(), 2) : null;

                // Resolve competency text from DB settings or fallback
                $subjectId = $schedule->subject?->id;
                $setting = $subjectId ? ($competencySettings[$subjectId] ?? null) : null;

                if ($setting) {
                    $capaian = $setting->resolveForScore($finalScore);
                } else {
                    $capaian = $this->generateCapaianKompetensi($finalScore, $schedule->subject?->name ?? 'pembelajaran');
                }

                return [
                    'subject_code' => $schedule->subject?->code ?? '-',
                    'subject_name' => $schedule->subject?->name ?? '-',
                    'teacher_name' => $schedule->teacher?->user?->name ?? '-',
                    'final_grade' => $finalScore,
                    'capaian_kompetensi' => $capaian,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{S: int, I: int, A: int}
     */
    private function buildAttendanceSummary(SchoolClass $schoolClass, Student $student): array
    {
        $attendanceRecords = $schoolClass->schedules->flatMap(function ($schedule) use ($student) {
            return $schedule->attendances->where('student_id', $student->user_id);
        });

        return [
            'S' => $attendanceRecords->where('status', 'sick')->count(),
            'I' => $attendanceRecords->where('status', 'permission')->count(),
            'A' => $attendanceRecords->where('status', 'alpa')->count(),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolvePredicate(?float $finalScore): array
    {
        if ($finalScore === null) {
            return ['-', '-'];
        }

        return match (true) {
            $finalScore >= 90 => ['A', 'Sangat Baik, mampu memahami materi secara sangat baik dan konsisten.'],
            $finalScore >= 80 => ['B', 'Baik, mampu memahami materi dengan baik dan cukup konsisten.'],
            $finalScore >= 70 => ['C', 'Cukup, perlu meningkatkan konsistensi pemahaman materi.'],
            default => ['D', 'Perlu bimbingan intensif untuk mencapai ketuntasan belajar.'],
        };
    }

    /**
     * Generate dynamic "Capaian Kompetensi" text for Kurikulum Merdeka report cards.
     */
    private function generateCapaianKompetensi(?float $finalScore, string $subjectName): string
    {
        if ($finalScore === null) {
            return 'Belum ada data penilaian.';
        }

        if ($finalScore >= 85) {
            return "Mencapai Kompetensi dengan sangat baik dalam memahami materi pembelajaran {$subjectName}.";
        }

        if ($finalScore >= 75) {
            return "Mencapai kompetensi dengan baik dalam memahami materi pembelajaran {$subjectName}.";
        }

        return "Perlu peningkatan dalam hal memahami materi pembelajaran {$subjectName}.";
    }

    private function throwIncompleteReportException(array $missingData): never
    {
        throw new HttpException(
            422,
            json_encode([
                'success' => false,
                'message' => 'Rapor belum dapat diunduh. Terdapat data akademis yang belum lengkap.',
                'missing_data' => $missingData,
            ], JSON_UNESCAPED_UNICODE)
        );
    }
}
