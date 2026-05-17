<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GradeAggregationService
{
    /**
     * Kalkulasi rata-rata nilai per mata pelajaran untuk satu siswa.
     */
    public function getStudentAggregate(int $studentId, int $classId): array
    {
        $aggregates = DB::table('schedules')
            ->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
            ->join('assignments', 'schedules.id', '=', 'assignments.schedule_id')
            ->join('submissions', 'assignments.id', '=', 'submissions.assignment_id')
            ->join('grades', 'submissions.id', '=', 'grades.submission_id')
            ->where('schedules.class_id', $classId)
            ->where('submissions.student_id', $studentId)
            ->select(
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                DB::raw('ROUND(AVG(grades.score), 2) as final_grade'),
                DB::raw('COUNT(grades.id) as total_graded_assignments')
            )
            ->groupBy('subjects.id', 'subjects.name', 'subjects.code')
            ->get();

        return $aggregates->toArray();
    }

    /**
     * Kalkulasi rata-rata nilai seluruh siswa dalam satu kelas/jadwal untuk Guru.
     */
    public function getClassAggregate(int $teacherId, int $scheduleId): array
    {
        $schedule = Schedule::findOrFail($scheduleId);

        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, "Akses ditolak: Anda tidak mengajar di jadwal ini.");
        }

        $aggregates = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('submissions', function ($join) use ($scheduleId) {
                $join->on('students.user_id', '=', 'submissions.student_id')
                     ->whereIn('submissions.assignment_id', function ($query) use ($scheduleId) {
                         $query->select('id')->from('assignments')->where('schedule_id', $scheduleId);
                     });
            })
            ->leftJoin('grades', 'submissions.id', '=', 'grades.submission_id')
            ->where('students.class_id', $schedule->class_id)
            ->where('students.status', 'active')
            ->select(
                'students.user_id as student_id',
                'students.nisn',
                'users.name as student_name',
                DB::raw('ROUND(AVG(grades.score), 2) as final_grade'),
                DB::raw('COUNT(grades.id) as total_graded_assignments')
            )
            ->groupBy('students.user_id', 'students.nisn', 'users.name')
            ->orderBy('users.name')
            ->get();

        return $aggregates->toArray();
    }
}