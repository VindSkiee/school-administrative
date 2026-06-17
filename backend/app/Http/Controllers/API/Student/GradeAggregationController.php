<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Subject;

class GradeAggregationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        /** @var \App\Models\User $user */
        $student = $user->student()->with('classes')->first();
        $activeClass = $student->classes->first();

        if (!$activeClass) {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        // PERF FIX: replaced N+1 (1 subjects query + N per-subject queries) with single query for all data
        $allAssignments = DB::table('assignments')
            ->join('schedules', 'assignments.schedule_id', '=', 'schedules.id')
            ->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('submissions', function ($join) use ($student) {
                $join->on('assignments.id', '=', 'submissions.assignment_id')
                     ->where('submissions.student_id', '=', $student->user_id);
            })
            ->leftJoin('grades', 'submissions.id', '=', 'grades.submission_id')
            ->where('schedules.class_id', $activeClass->id)
            ->select(
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'assignments.id as assignment_id',
                'assignments.title as assignment_title',
                'assignments.type as assignment_type',
                'grades.score as assignment_score'
            )
            ->orderBy('subjects.id')
            ->orderBy('assignments.id')
            ->get();

        // PERF FIX: replaced N+1 — group by subject in PHP from single query result
        $groupedBySubject = $allAssignments->groupBy('subject_id');

        $reportData = [];
        foreach ($groupedBySubject as $subjectId => $assignments) {
            $first = $assignments->first();

            $gradedTasks = $assignments->whereNotNull('assignment_score');
            $finalGrade = $gradedTasks->count() > 0 ? round($gradedTasks->avg('assignment_score'), 2) : null;

            $reportData[] = [
                'subject_id'   => $subjectId,
                'subject_name' => $first->subject_name,
                'subject_code' => $first->subject_code,
                'final_grade'  => $finalGrade,
                'total_graded_assignments' => $gradedTasks->count(),
                'details'      => $assignments->map(function ($a) {
                    return [
                        'title' => $a->assignment_title,
                        'type'  => $a->assignment_type,
                        'score' => $a->assignment_score !== null ? (float) $a->assignment_score : null,
                    ];
                })->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $reportData,
        ]);
    }
}
