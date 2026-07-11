<?php

namespace App\Services;

use App\Models\GradingSetting;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GradeAggregationService
{
    /**
     * Default weights used when no GradingSetting is configured for the academic year.
     */
    private const DEFAULT_WEIGHTS = [
        'task' => 30,
        'ujian_harian' => 10,
        'uts' => 25,
        'uas' => 25,
        'attendance' => 10,
    ];

    /**
     * Exam types that can have remedial assignments.
     */
    private const EXAM_TYPES = ['ujian_harian', 'uts', 'uas'];

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
            throw new HttpException(403, 'Akses ditolak: Anda tidak mengajar di jadwal ini.');
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
     * Uses a "walking average" approach with remedial resolution:
     * - If an exam type has a remedial grade, resolve it based on remedial_mode (replace/average/custom)
     * - Only include weights for types that have graded data
     * - Attendance is always included if attendance_weight > 0
     */
    public function calculateWeightedAverage(int $studentId, int $classId, int $academicYearId): array
    {
        // 1. Fetch grading settings
        $settings = GradingSetting::where('academic_year_id', $academicYearId)->first();

        $weights = [
            'task' => $settings ? $settings->task_weight : self::DEFAULT_WEIGHTS['task'],
            'ujian_harian' => $settings ? $settings->daily_exam_weight : self::DEFAULT_WEIGHTS['ujian_harian'],
            'uts' => $settings ? $settings->uts_weight : self::DEFAULT_WEIGHTS['uts'],
            'uas' => $settings ? $settings->uas_weight : self::DEFAULT_WEIGHTS['uas'],
            'attendance' => $settings ? $settings->attendance_weight : self::DEFAULT_WEIGHTS['attendance'],
        ];

        // 2. Fetch all graded scores with assignment info + remedial mode from parent grade
        $gradedScores = DB::table('grades')
            ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->join('schedules', 'assignments.schedule_id', '=', 'schedules.id')
            ->where('submissions.student_id', $studentId)
            ->where('schedules.class_id', $classId)
            ->where('schedules.academic_year_id', $academicYearId)
            ->select(
                'assignments.id as assignment_id',
                'assignments.type',
                'assignments.is_remedial',
                'assignments.linked_assignment_id',
                'grades.score',
                'grades.remedial_mode'
            )
            ->get();

        // 3. Separate regular vs remedial scores, and collect remedial_mode from parent exam grades
        $regularScoresByType = ['task' => [], 'ujian_harian' => [], 'uts' => [], 'uas' => []];
        $remedialScoresByParentType = []; // parent_assignment_id => [scores]
        $remedialModes = []; // parent_assignment_id => remedial_mode

        foreach ($gradedScores as $row) {
            if ($row->is_remedial && $row->linked_assignment_id) {
                // This is a remedial submission
                // Find the parent assignment type
                $parentType = $this->resolveParentType($gradedScores, $row->linked_assignment_id);
                if ($parentType) {
                    $remedialScoresByParentType[$row->linked_assignment_id][] = (float) $row->score;
                }
            } else {
                // Regular submission
                $regularScoresByType[$row->type][] = (float) $row->score;

                // Store remedial_mode from the parent grade if present
                if ($row->remedial_mode && in_array($row->type, self::EXAM_TYPES)) {
                    $remedialModes[$row->assignment_id] = $row->remedial_mode;
                }
            }
        }

        // 4. Resolve scores per type (apply remedial logic for exam types)
        $resolvedScoresByType = [];

        foreach (['task' => [], 'ujian_harian' => [], 'uts' => [], 'uas' => []] as $type => $_) {
            if ($type === 'task') {
                $resolvedScoresByType['task'] = $regularScoresByType['task'];

                continue;
            }

            $regularScores = $regularScoresByType[$type];
            if (empty($regularScores)) {
                $resolvedScoresByType[$type] = [];

                continue;
            }

            // For exam types, check each parent assignment for remedial
            $resolvedScores = [];
            foreach ($regularScores as $index => $score) {
                // Find the parent assignment ID for this score
                $parentAssignmentId = $this->findParentAssignmentId($gradedScores, $type, $index);

                if ($parentAssignmentId && isset($remedialScoresByParentType[$parentAssignmentId])) {
                    // This exam has remedial scores
                    $remedialScores = $remedialScoresByParentType[$parentAssignmentId];
                    $remedialMode = $remedialModes[$parentAssignmentId] ?? 'replace';

                    $remedialAvg = array_sum($remedialScores) / count($remedialScores);

                    $resolvedScores[] = $this->resolveRemedialScore($score, $remedialAvg, $remedialMode);
                } else {
                    $resolvedScores[] = $score;
                }
            }

            $resolvedScoresByType[$type] = $resolvedScores;
        }

        // 5. Calculate averages per type from resolved scores
        $taskAvg = ! empty($resolvedScoresByType['task'])
            ? array_sum($resolvedScoresByType['task']) / count($resolvedScoresByType['task'])
            : null;
        $uhAvg = ! empty($resolvedScoresByType['ujian_harian'])
            ? array_sum($resolvedScoresByType['ujian_harian']) / count($resolvedScoresByType['ujian_harian'])
            : null;
        $utsAvg = ! empty($resolvedScoresByType['uts'])
            ? array_sum($resolvedScoresByType['uts']) / count($resolvedScoresByType['uts'])
            : null;
        $uasAvg = ! empty($resolvedScoresByType['uas'])
            ? array_sum($resolvedScoresByType['uas']) / count($resolvedScoresByType['uas'])
            : null;

        // 6. Calculate attendance rate
        $allScheduleIds = Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id');

        $attendanceStats = DB::table('attendances')
            ->whereIn('schedule_id', $allScheduleIds)
            ->where('student_id', $studentId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count")
            )
            ->first();

        $totalAttendances = $attendanceStats->total ?? 0;
        $presentCount = $attendanceStats->present_count ?? 0;
        $attendanceRate = $totalAttendances > 0
            ? round(($presentCount / $totalAttendances) * 100, 2)
            : 100;

        // 7. Walking average: only include weights for types that have graded data
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
            : 0;

        return [
            'final_score' => $finalScore,
            'breakdown' => [
                'task' => [
                    'average' => $taskAvg !== null ? round($taskAvg, 2) : null,
                    'weight' => $weights['task'],
                    'count' => count($resolvedScoresByType['task']),
                ],
                'ujian_harian' => [
                    'average' => $uhAvg !== null ? round($uhAvg, 2) : null,
                    'weight' => $weights['ujian_harian'],
                    'count' => count($resolvedScoresByType['ujian_harian']),
                ],
                'uts' => [
                    'average' => $utsAvg !== null ? round($utsAvg, 2) : null,
                    'weight' => $weights['uts'],
                    'count' => count($resolvedScoresByType['uts']),
                ],
                'uas' => [
                    'average' => $uasAvg !== null ? round($uasAvg, 2) : null,
                    'weight' => $weights['uas'],
                    'count' => count($resolvedScoresByType['uas']),
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

    /**
     * Resolve a final exam score based on remedial mode.
     */
    private function resolveRemedialScore(float $examScore, float $remedialScore, string $mode): float
    {
        return match ($mode) {
            'replace' => max($examScore, $remedialScore),
            'average' => round(($examScore + $remedialScore) / 2, 2),
            'custom' => $remedialScore, // custom_score was already set as the grade score by the teacher
            default => max($examScore, $remedialScore),
        };
    }

    /**
     * Find the parent assignment ID for a remedial row by its linked_assignment_id.
     */
    private function resolveParentType($gradedScores, int $linkedAssignmentId): ?string
    {
        foreach ($gradedScores as $row) {
            if ($row->assignment_id == $linkedAssignmentId) {
                return $row->type;
            }
        }

        return null;
    }

    /**
     * Find the parent assignment ID for a regular score at a given index within its type.
     */
    private function findParentAssignmentId($gradedScores, string $type, int $targetIndex): ?int
    {
        $index = 0;
        foreach ($gradedScores as $row) {
            if ($row->type === $type && ! $row->is_remedial) {
                if ($index === $targetIndex) {
                    return (int) $row->assignment_id;
                }
                $index++;
            }
        }

        return null;
    }
}
