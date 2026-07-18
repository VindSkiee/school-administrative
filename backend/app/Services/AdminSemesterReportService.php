<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\GradingSetting;
use App\Models\Principal;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEskul;
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

        // Find odd semester class for dual-semester promotion evaluation
        $oddSemesterYear = $this->findOddSemesterYear($academicYear);
        $oddSemesterClass = $oddSemesterYear
            ? $this->findOddSemesterClass($schoolClass, $oddSemesterYear)
            : null;

        return [
            'school_name' => config('app.school_name', 'SMP NEGERI 5 PURWAKARTA'),
            'school_address' => config('app.school_address', 'Jl. Kolonel Singawinata No. 97 Purwakarta'),
            'academic_year' => $academicYear->name,
            'semester' => $academicYear->semester,
            'semester_label' => $academicYear->semester === 'odd' ? 'Ganjil' : 'Genap',
            'phase' => $academicYear->phase ?? 'D',
            'student_name' => $student->user?->name ?? '-',
            'student_nis' => $student->nis ?? '-',
            'student_nisn' => $student->nisn ?? '-',
            'class_name' => $schoolClass->name,
            'homeroom_teacher_name' => $schoolClass->homeroomTeacher?->user?->name ?? '-',
            'homeroom_teacher_nip' => $schoolClass->homeroomTeacher?->nip ?? '-',
            'principal_name' => $principal?->user?->name ?? '-',
            'principal_nip' => $principal?->nip ?? '-',
            'generated_at' => now()->format('d M Y'),
            'homeroom_note' => $this->getStudentNote($schoolClass->id, $student->user_id),
            'keterangan_kenaikan_kelas' => $this->resolvePromotionStatus(
                $student,
                $schoolClass,
                $academicYear,
                $oddSemesterClass,
                $oddSemesterYear,
            ),
            'results' => $this->buildSubjectResults($schoolClass, $student, $academicYear),
            'attendance' => $this->buildAttendanceSummary($schoolClass, $student),
            'eskul_results' => $this->buildEskulResults($student, $academicYear),
        ];
    }

    /**
     * Get the homeroom teacher's note for a student in a class.
     */
    private function getStudentNote(int $classId, int $studentId): string
    {
        $note = DB::table('class_student')
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->value('note');

        return $note ?? '-';
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
     * Build eskul results for a student in a given academic year.
     *
     * @return array<int, array{eskul_name: string, score: float|null, description: string|null}>
     */
    private function buildEskulResults(Student $student, AcademicYear $academicYear): array
    {
        $studentEskuls = StudentEskul::where('student_id', $student->user_id)
            ->where('academic_year_id', $academicYear->id)
            ->with('eskul:id,name')
            ->get();

        return $studentEskuls->map(fn (StudentEskul $se) => [
            'eskul_name' => $se->eskul?->name ?? '-',
            'score' => $se->score,
            'description' => $se->description ?? '-',
        ])->toArray();
    }

    /**
     * Resolve grade index label (A/B/C/D) from a final score.
     */
    public function resolveGradeIndex(?float $finalScore): string
    {
        if ($finalScore === null) {
            return '-';
        }

        return match (true) {
            $finalScore >= 90 => 'A',
            $finalScore >= 80 => 'B',
            $finalScore >= 70 => 'C',
            default => 'D',
        };
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

    /**
     * Resolve per-student promotion status based on global min_score_to_pass threshold.
     * Checks every subject's final grade against the threshold.
     *
     * @param  SchoolClass  $schoolClass  The student's class in the current (even) semester
     * @param  AcademicYear  $academicYear  The current (even) academic year
     * @param  SchoolClass|null  $oddSemesterClass  The student's class in the odd semester (for dual-semester evaluation)
     * @param  AcademicYear|null  $oddSemesterYear  The odd semester academic year
     */
    private function resolvePromotionStatus(
        Student $student,
        SchoolClass $schoolClass,
        AcademicYear $academicYear,
        ?SchoolClass $oddSemesterClass = null,
        ?AcademicYear $oddSemesterYear = null,
    ): string {
        $name = trim($schoolClass->name);
        preg_match('/^(\d+)/', $name, $matches);
        $currentGrade = isset($matches[1]) ? (int) $matches[1] : 0;

        $gradingSetting = $academicYear->gradingSetting;
        $minScore = $gradingSetting?->min_score_to_pass ?? 60;

        // Calculate weighted scores per subject across both semesters
        $subjectScores = $this->calculateDualSemesterSubjectScores(
            $student,
            $schoolClass,
            $academicYear,
            $oddSemesterClass,
            $oddSemesterYear,
        );

        $failedSubjects = [];

        foreach ($subjectScores as $subjectName => $score) {
            if ($score === null || $score < $minScore) {
                $failedSubjects[] = $subjectName;
            }
        }

        // Grade 9 special case
        if ($currentGrade >= 9) {
            return empty($failedSubjects) ? 'Lulus / Tamat Belajar' : 'Tidak Lulus';
        }

        if (empty($failedSubjects)) {
            $romanMap = [7 => 'VIII', 8 => 'IX', 10 => 'X'];
            $nextRoman = $romanMap[$currentGrade] ?? (string) ($currentGrade + 1);

            return "Naik ke kelas {$nextRoman}";
        }

        return 'Tidak Tuntas';
    }

    /**
     * Calculate weighted subject scores combining both odd and even semesters.
     *
     * For each subject, computes the weighted average per semester using GradingSetting weights,
     * then averages the two semester results into a single final grade.
     *
     * @return array<string, float|null> subject name → final weighted grade
     */
    public function calculateDualSemesterSubjectScores(
        Student $student,
        SchoolClass $evenClass,
        AcademicYear $evenYear,
        ?SchoolClass $oddClass,
        ?AcademicYear $oddYear,
    ): array {
        // PERF FIX: Pre-fetch all attendance records in bulk to avoid N+1 in calculateWeightedSubjectScoresForSemester
        $evenAttendanceMap = $this->bulkFetchAttendanceRates($student->user_id, $evenClass, $evenYear);
        $evenScores = $this->calculateWeightedSubjectScoresForSemester($student, $evenClass, $evenYear, $evenAttendanceMap);

        $oddScores = [];
        if ($oddClass && $oddYear) {
            $oddAttendanceMap = $this->bulkFetchAttendanceRates($student->user_id, $oddClass, $oddYear);
            $oddScores = $this->calculateWeightedSubjectScoresForSemester($student, $oddClass, $oddYear, $oddAttendanceMap);
        }

        // Merge all subject names from both semesters
        $allSubjectNames = array_unique(array_merge(array_keys($evenScores), array_keys($oddScores)));
        $result = [];

        foreach ($allSubjectNames as $subjectName) {
            $evenGrade = $evenScores[$subjectName] ?? null;
            $oddGrade = $oddScores[$subjectName] ?? null;

            // Average both semesters; if one is missing, use the other
            if ($evenGrade !== null && $oddGrade !== null) {
                $result[$subjectName] = round(($evenGrade + $oddGrade) / 2, 2);
            } else {
                $result[$subjectName] = $evenGrade ?? $oddGrade;
            }
        }

        return $result;
    }

    /**
     * Calculate weighted scores per subject for a single semester.
     *
     * Uses GradingSetting weights (task, ujian_harian, uts, uas, attendance) with walking average approach:
     * only includes weights for assignment types that have graded data.
     * Applies remedial resolution for exam types (ujian_harian, uts, uas).
     *
     * @return array<string, float|null> subject name → weighted final grade
     */
    private function calculateWeightedSubjectScoresForSemester(
        Student $student,
        SchoolClass $schoolClass,
        AcademicYear $academicYear,
        ?array $attendanceMap = null,
    ): array {
        $gradingSetting = $academicYear->gradingSetting;
        $weights = [
            'task' => $gradingSetting?->task_weight ?? 30,
            'ujian_harian' => $gradingSetting?->daily_exam_weight ?? 10,
            'uts' => $gradingSetting?->uts_weight ?? 25,
            'uas' => $gradingSetting?->uas_weight ?? 25,
            'attendance' => $gradingSetting?->attendance_weight ?? 10,
        ];

        $result = [];

        foreach ($schoolClass->schedules as $schedule) {
            $subjectName = $schedule->subject?->name ?? '-';
            $scoresByType = ['task' => [], 'ujian_harian' => [], 'uts' => [], 'uas' => []];
            $remedialLookup = []; // linked_assignment_id => ['mode' => ..., 'scores' => [...]]

            foreach ($schedule->assignments as $assignment) {
                $submission = $assignment->submissions->firstWhere('student_id', $student->user_id);

                if ($submission?->grade?->score !== null) {
                    $type = $assignment->type ?? 'task';

                    if ($assignment->is_remedial && $assignment->linked_assignment_id) {
                        // Store remedial data for resolution
                        $linkedId = $assignment->linked_assignment_id;
                        if (! isset($remedialLookup[$linkedId])) {
                            $remedialLookup[$linkedId] = ['mode' => 'replace', 'scores' => []];
                        }
                        $remedialLookup[$linkedId]['scores'][] = (float) $submission->grade->score;

                        // Get remedial_mode from parent's grade
                        $parentSubmission = $schedule->assignments
                            ->firstWhere('id', $linkedId)?->submissions
                            ->firstWhere('student_id', $student->user_id);
                        if ($parentSubmission?->grade?->remedial_mode) {
                            $remedialLookup[$linkedId]['mode'] = $parentSubmission->grade->remedial_mode;
                        }
                    } else {
                        $scoresByType[$type][] = (float) $submission->grade->score;
                    }
                }
            }

            // Apply remedial resolution for exam types
            foreach ($schedule->assignments as $assignment) {
                if (! in_array($assignment->type, ['ujian_harian', 'uts', 'uas'])) {
                    continue;
                }

                if (isset($remedialLookup[$assignment->id])) {
                    $remedial = $remedialLookup[$assignment->id];
                    $parentSubmission = $assignment->submissions->firstWhere('student_id', $student->user_id);

                    if ($parentSubmission?->grade?->score !== null) {
                        $examScore = (float) $parentSubmission->grade->score;
                        $remedialAvg = array_sum($remedial['scores']) / count($remedial['scores']);

                        // Resolve and replace the exam score in scoresByType
                        $resolvedScore = match ($remedial['mode']) {
                            'replace' => max($examScore, $remedialAvg),
                            'average' => round(($examScore + $remedialAvg) / 2, 2),
                            'custom' => $remedialAvg,
                            default => max($examScore, $remedialAvg),
                        };

                        // Replace the score in the type array
                        $typeScores = &$scoresByType[$assignment->type];
                        foreach ($typeScores as &$s) {
                            if ($s === $examScore) {
                                $s = $resolvedScore;
                                break;
                            }
                        }
                        unset($s, $typeScores);
                    }
                }
            }

            // Average per type
            $taskAvg = ! empty($scoresByType['task'])
                ? array_sum($scoresByType['task']) / count($scoresByType['task'])
                : null;
            $uhAvg = ! empty($scoresByType['ujian_harian'])
                ? array_sum($scoresByType['ujian_harian']) / count($scoresByType['ujian_harian'])
                : null;
            $utsAvg = ! empty($scoresByType['uts'])
                ? array_sum($scoresByType['uts']) / count($scoresByType['uts'])
                : null;
            $uasAvg = ! empty($scoresByType['uas'])
                ? array_sum($scoresByType['uas']) / count($scoresByType['uas'])
                : null;

            // PERF FIX: Use pre-fetched attendance map instead of per-schedule query
            $attendanceRate = $attendanceMap[$schedule->id] ?? 100;

            // Walking average: only include weights for types with data
            $activeWeight = 0;
            $weightedSum = 0;

            if ($taskAvg !== null) {
                $weightedSum += $taskAvg * $weights['task'];
                $activeWeight += $weights['task'];
            }
            if ($uhAvg !== null) {
                $weightedSum += $uhAvg * $weights['ujian_harian'];
                $activeWeight += $weights['ujian_harian'];
            }
            if ($utsAvg !== null) {
                $weightedSum += $utsAvg * $weights['uts'];
                $activeWeight += $weights['uts'];
            }
            if ($uasAvg !== null) {
                $weightedSum += $uasAvg * $weights['uas'];
                $activeWeight += $weights['uas'];
            }
            if ($weights['attendance'] > 0) {
                $weightedSum += $attendanceRate * $weights['attendance'];
                $activeWeight += $weights['attendance'];
            }

            $finalScore = $activeWeight > 0
                ? round($weightedSum / $activeWeight, 2)
                : null;

            $result[$subjectName] = $finalScore;
        }

        return $result;
    }

    /**
     * Calculate attendance rate for a student on a specific schedule.
     */
    private function calculateAttendanceRate(int $studentId, Schedule $schedule): float
    {
        $stats = DB::table('attendances')
            ->where('schedule_id', $schedule->id)
            ->where('student_id', $studentId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
            )
            ->first();

        $total = $stats->total ?? 0;
        $present = $stats->present_count ?? 0;

        return $total > 0 ? round(($present / $total) * 100, 2) : 100;
    }

    /**
     * PERF FIX: Bulk-fetch attendance rates for all schedules in a class.
     * Returns map of schedule_id => attendance_rate (float).
     * Replaces N+1 calls to calculateAttendanceRate().
     *
     * @return array<int, float> schedule_id => attendance rate (0-100)
     */
    private function bulkFetchAttendanceRates(int $studentId, SchoolClass $schoolClass, AcademicYear $academicYear): array
    {
        $scheduleIds = $schoolClass->schedules->pluck('id');

        if ($scheduleIds->isEmpty()) {
            return [];
        }

        $stats = DB::table('attendances')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('student_id', $studentId)
            ->select(
                'schedule_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
            )
            ->groupBy('schedule_id')
            ->get();

        $result = [];
        foreach ($stats as $row) {
            $total = (int) $row->total;
            $present = (int) $row->present_count;
            $result[$row->schedule_id] = $total > 0 ? round(($present / $total) * 100, 2) : 100;
        }

        return $result;
    }

    /**
     * Find the matching class in the odd semester for a given even-semester class.
     * Matches by class name (e.g. "7A" in even → "7A" in odd).
     */
    public function findOddSemesterClass(SchoolClass $evenClass, AcademicYear $oddYear): ?SchoolClass
    {
        return SchoolClass::where('academic_year_id', $oddYear->id)
            ->where('name', $evenClass->name)
            ->with([
                'schedules' => function ($query) use ($oddYear): void {
                    $query->where('academic_year_id', $oddYear->id)
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
     * Find the odd semester academic year corresponding to the given even semester year.
     * Matches by year range in the name (e.g. "2025/2026 Genap" → "2025/2026 Ganjil").
     */
    public function findOddSemesterYear(AcademicYear $evenYear): ?AcademicYear
    {
        // Extract year range from name (e.g. "2025/2026 Genap" → "2025/2026")
        preg_match('/(\d{4}\/\d{4})/', $evenYear->name, $matches);
        $yearRange = $matches[1] ?? null;

        if (! $yearRange) {
            return null;
        }

        return AcademicYear::where('semester', 'odd')
            ->where('name', 'like', "%{$yearRange}%")
            ->first();
    }

    /**
     * Evaluate promotion status for a single student using dual-semester grades.
     * Returns a structured result with pass/fail and failed subjects.
     *
     * @return array{passed: bool, failed_subjects: list<string>, reason: string}
     */
    public function evaluateStudentPromotion(
        Student $student,
        SchoolClass $evenClass,
        AcademicYear $evenYear,
        ?SchoolClass $oddClass = null,
        ?AcademicYear $oddYear = null,
    ): array {
        $name = trim($evenClass->name);
        preg_match('/^(\d+)/', $name, $matches);
        $currentGrade = isset($matches[1]) ? (int) $matches[1] : 0;

        $gradingSetting = $evenYear->gradingSetting;
        $minScore = $gradingSetting?->min_score_to_pass ?? 60;

        $subjectScores = $this->calculateDualSemesterSubjectScores(
            $student,
            $evenClass,
            $evenYear,
            $oddClass,
            $oddYear,
        );

        $failedSubjects = [];

        foreach ($subjectScores as $subjectName => $score) {
            if ($score === null || $score < $minScore) {
                $failedSubjects[] = $subjectName;
            }
        }

        $passed = empty($failedSubjects);

        // Grade 9: graduated vs not graduated
        if ($currentGrade >= 9) {
            return [
                'passed' => $passed,
                'failed_subjects' => $failedSubjects,
                'reason' => $passed
                    ? 'Lulus / Tamat Belajar'
                    : 'Nilai '.implode(', ', $failedSubjects).' di bawah ambang batas ('.$minScore.')',
            ];
        }

        // Grade 7-8: promoted vs repeated
        return [
            'passed' => $passed,
            'failed_subjects' => $failedSubjects,
            'reason' => $passed
                ? 'Naik kelas'
                : 'Nilai '.implode(', ', $failedSubjects).' di bawah ambang batas ('.$minScore.')',
        ];
    }
}
