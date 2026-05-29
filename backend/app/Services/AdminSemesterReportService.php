<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Principal;
use App\Models\SchoolClass;
use App\Models\Student;
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
    public function getAcademicYearReadiness(int $academicYearId): array
    {
        $classes = SchoolClass::query()
            ->where('academic_year_id', $academicYearId)
            ->with([
                'students.user',
                'schedules.subject',
                'schedules.attendances',
                'schedules.assignments.submissions.grade',
            ])
            ->orderBy('name')
            ->get();

        $data = [];

        foreach ($classes as $schoolClass) {
            foreach ($schoolClass->students as $student) {
                $data[] = $this->buildStudentReadinessPayload($schoolClass, $student);
            }
        }

        return [
            'is_all_ready' => collect($data)->every(fn (array $studentReadiness): bool => $studentReadiness['is_ready']),
            'data' => $data,
        ];
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

        $reportData = $this->buildReportData($academicYear, $student, $schoolClass);

        return $this->pdfService->generateSemesterReportPdf($reportData, $student->user->name);
    }

    private function findStudentClassForAcademicYear(AcademicYear $academicYear, Student $student): ?SchoolClass
    {
        return $student->classes()
            ->where('academic_year_id', $academicYear->id)
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
     * @return array<string, mixed>
     */
    private function buildReportData(AcademicYear $academicYear, Student $student, SchoolClass $schoolClass): array
    {
        $principal = Principal::query()->with('user')->first();

        return [
            'school_name' => config('app.school_name', ucwords(str_replace(['-', '_'], ' ', (string) config('app.name', 'Nama Sekolah')))),
            'school_address' => config('app.school_address', 'Alamat sekolah belum diatur'),
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
            'results' => $this->buildSubjectResults($schoolClass, $student),
            'attendance' => $this->buildAttendanceSummary($schoolClass, $student),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSubjectResults(SchoolClass $schoolClass, Student $student): array
    {
        return $schoolClass->schedules
            ->map(function ($schedule) use ($student): array {
                $scores = collect();

                foreach ($schedule->assignments as $assignment) {
                    $submission = $assignment->submissions->firstWhere('student_id', $student->user_id);

                    if ($submission?->grade?->score !== null) {
                        $scores->push((float) $submission->grade->score);
                    }
                }

                $finalScore = $scores->isNotEmpty() ? round($scores->avg(), 2) : null;
                [$predicate, $description] = $this->resolvePredicate($finalScore);

                return [
                    'subject_code' => $schedule->subject?->code ?? '-',
                    'subject_name' => $schedule->subject?->name ?? '-',
                    'teacher_name' => $schedule->teacher?->user?->name ?? '-',
                    'final_grade' => $finalScore,
                    'predicate' => $predicate,
                    'description' => $description,
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
