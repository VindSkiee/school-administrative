<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReportValidationService
{
    /**
     * Run 3 strict eligibility checks before allowing PDF download.
     *
     * @throws HttpException
     */
    public function checkEligibility(Student $student, AcademicYear $academicYear, SchoolClass $class): void
    {
        $this->checkPublishStatus($academicYear);
        $this->checkAttendanceWeeks($student, $academicYear);
        $this->checkSubjectCompletion($student, $class, $academicYear);
    }

    /**
     * 1. Publish Status Check
     */
    private function checkPublishStatus(AcademicYear $academicYear): void
    {
        if (! $academicYear->is_report_published) {
            throw new HttpException(403, 'Rapor belum dipublikasikan oleh Admin.');
        }
    }

    /**
     * 2. Attendance Check (minimum 12 distinct weeks)
     */
    private function checkAttendanceWeeks(Student $student, AcademicYear $academicYear): void
    {
        $scheduleIds = Schedule::where('academic_year_id', $academicYear->id)
            ->whereHas('schoolClass.students', function ($q) use ($student) {
                $q->where('students.user_id', $student->user_id);
            })
            ->pluck('id');

        $distinctWeeks = DB::table('attendances')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('student_id', $student->user_id)
            ->selectRaw('COUNT(DISTINCT YEARWEEK(date, 1)) as week_count')
            ->value('week_count');

        if ((int) $distinctWeeks < 12) {
            throw new HttpException(422, 'Syarat kehadiran minimal 12 minggu belum terpenuhi.');
        }
    }

    /**
     * 3. Subject Completion Check
     * Each subject must have at least 1 graded task, 1 UTS, and 1 UAS.
     */
    private function checkSubjectCompletion(Student $student, SchoolClass $class, AcademicYear $academicYear): void
    {
        $schedules = Schedule::with('subject')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $academicYear->id)
            ->get();

        foreach ($schedules as $schedule) {
            $subjectName = $schedule->subject?->name ?? 'Tanpa Nama';

            // Check each required type has at least 1 graded submission
            foreach (['task', 'uts', 'uas'] as $type) {
                $gradedCount = DB::table('grades')
                    ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
                    ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                    ->where('assignments.schedule_id', $schedule->id)
                    ->where('assignments.type', $type)
                    ->where('submissions.student_id', $student->user_id)
                    ->whereNotNull('grades.score')
                    ->count();

                if ($gradedCount < 1) {
                    $typeLabel = match ($type) {
                        'task' => 'Tugas',
                        'uts' => 'UTS',
                        'uas' => 'UAS',
                    };

                    throw new HttpException(
                        422,
                        "Nilai belum lengkap! Mapel {$subjectName} harus memiliki minimal 1 nilai Tugas, 1 UTS, dan 1 UAS. (Missing: {$typeLabel})"
                    );
                }
            }
        }
    }
}
