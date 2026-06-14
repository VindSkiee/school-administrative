<?php

namespace App\Services;

use App\Models\GradingSetting;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GradeAggregationService
{
    /**
     * Default weights used when no GradingSetting is configured for the academic year.
     */
    private const DEFAULT_WEIGHTS = [
        'task' => 40,
        'uts' => 25,
        'uas' => 25,
        'attendance' => 10,
    ];

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

    /**
     * Calculate the weighted average for a student across all subjects in a class for a given academic year.
     *
     * Uses a "walking average" approach: if UTS or UAS hasn't been graded yet,
     * their weight is excluded from the divisor so the student is not penalized.
     * Attendance is always included if attendance_weight > 0.
     *
     * @param int $studentId The student's user_id
     * @param int $classId The class ID
     * @param int $academicYearId The academic year ID
     * @return array Weighted score breakdown and final score
     */
    public function calculateWeightedAverage(int $studentId, int $classId, int $academicYearId): array
    {
        // 1. Fetch grading settings for the academic year, fallback to defaults
        $settings = GradingSetting::where('academic_year_id', $academicYearId)->first();

        $weights = [
            'task' => $settings ? $settings->task_weight : self::DEFAULT_WEIGHTS['task'],
            'uts' => $settings ? $settings->uts_weight : self::DEFAULT_WEIGHTS['uts'],
            'uas' => $settings ? $settings->uas_weight : self::DEFAULT_WEIGHTS['uas'],
            'attendance' => $settings ? $settings->attendance_weight : self::DEFAULT_WEIGHTS['attendance'],
        ];

        // 2. Fetch all graded submissions grouped by assignment type
        $gradedScores = DB::table('grades')
            ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->join('schedules', 'assignments.schedule_id', '=', 'schedules.id')
            ->where('submissions.student_id', $studentId)
            ->where('schedules.class_id', $classId)
            ->where('schedules.academic_year_id', $academicYearId)
            ->select(
                'assignments.type',
                'grades.score'
            )
            ->get();

        // 3. Group scores by assignment type
        $scoresByType = [
            'task' => [],
            'uts' => [],
            'uas' => [],
        ];

        foreach ($gradedScores as $row) {
            $scoresByType[$row->type][] = (float) $row->score;
        }

        // 4. Calculate averages per type
        $taskAvg = ! empty($scoresByType['task'])
            ? array_sum($scoresByType['task']) / count($scoresByType['task'])
            : null;

        $utsAvg = ! empty($scoresByType['uts'])
            ? array_sum($scoresByType['uts']) / count($scoresByType['uts'])
            : null;

        $uasAvg = ! empty($scoresByType['uas'])
            ? array_sum($scoresByType['uas']) / count($scoresByType['uas'])
            : null;

        // 5. Calculate attendance rate for this student in this class
        $allScheduleIds = \App\Models\Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id');

        $totalAttendances = \App\Models\Attendance::whereIn('schedule_id', $allScheduleIds)
            ->where('student_id', $studentId)
            ->count();

        $presentCount = \App\Models\Attendance::whereIn('schedule_id', $allScheduleIds)
            ->where('student_id', $studentId)
            ->where('status', 'present')
            ->count();

        $attendanceRate = $totalAttendances > 0
            ? round(($presentCount / $totalAttendances) * 100, 2)
            : 100;

        // 6. Walking average: only include weights for types that have graded data
        $activeWeight = 0;
        $weightedSum = 0;

        if ($taskAvg !== null) {
            $weightedSum += $taskAvg * $weights['task'];
            $activeWeight += $weights['task'];
        }

        if ($utsAvg !== null) {
            $weightedSum += $utsAvg * $weights['uts'];
            $activeWeight += $weights['uts'];
        }

        if ($uasAvg !== null) {
            $weightedSum += $uasAvg * $weights['uas'];
            $activeWeight += $weights['uas'];
        }

        // Attendance is always included if its weight > 0
        if ($weights['attendance'] > 0) {
            $weightedSum += $attendanceRate * $weights['attendance'];
            $activeWeight += $weights['attendance'];
        }

        // Prevent division by zero if nothing is graded yet
        $finalScore = $activeWeight > 0
            ? round($weightedSum / $activeWeight, 2)
            : 0;

        return [
            'final_score' => $finalScore,
            'breakdown' => [
                'task' => [
                    'average' => $taskAvg !== null ? round($taskAvg, 2) : null,
                    'weight' => $weights['task'],
                    'count' => count($scoresByType['task']),
                ],
                'uts' => [
                    'average' => $utsAvg !== null ? round($utsAvg, 2) : null,
                    'weight' => $weights['uts'],
                    'count' => count($scoresByType['uts']),
                ],
                'uas' => [
                    'average' => $uasAvg !== null ? round($uasAvg, 2) : null,
                    'weight' => $weights['uas'],
                    'count' => count($scoresByType['uas']),
                ],
                'attendance' => [
                    'rate' => $attendanceRate,
                    'weight' => $weights['attendance'],
                ],
            ],
            'weights_used' => $weights,
            'active_divisor' => $activeWeight,
        ];
    }
}