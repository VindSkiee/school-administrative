<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeAggregationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        /** @var User $user */
        $student = $user->student()->with('classes')->first();

        // Accept optional academic_year_id — defaults to active year
        $yearId = $request->query('academic_year_id');
        $year = $yearId
            ? AcademicYear::find($yearId)
            : AcademicYear::active();

        if (! $year) {
            return response()->json(['error' => 'Tahun ajaran tidak ditemukan.'], 404);
        }

        $activeClass = $student->classes->firstWhere('academic_year_id', $year->id);

        if (! $activeClass) {
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
            ->where('schedules.academic_year_id', $year->id)
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

            // Weighted average — consistent with teacher's gradebook calculation
            $weights = [
                'task' => 30,
                'ujian_harian' => 10,
                'uts' => 25,
                'uas' => 25,
            ];

            $typeScores = [];
            foreach ($assignments as $a) {
                if ($a->assignment_score !== null) {
                    $type = $a->assignment_type;
                    if (! isset($typeScores[$type])) {
                        $typeScores[$type] = [];
                    }
                    $typeScores[$type][] = (float) $a->assignment_score;
                }
            }

            $weightedSum = 0;
            $activeWeight = 0;
            foreach ($typeScores as $type => $scores) {
                $avg = array_sum($scores) / count($scores);
                $w = $weights[$type] ?? 0;
                $weightedSum += $avg * $w;
                $activeWeight += $w;
            }

            $finalGrade = $activeWeight > 0 ? round($weightedSum / $activeWeight, 2) : null;
            $gradedCount = count($typeScores);

            $reportData[] = [
                'subject_id' => $subjectId,
                'subject_name' => $first->subject_name,
                'subject_code' => $first->subject_code,
                'final_grade' => $finalGrade,
                'total_graded_assignments' => $gradedCount,
                'details' => $assignments->map(function ($a) {
                    return [
                        'title' => $a->assignment_title,
                        'type' => $a->assignment_type,
                        'score' => $a->assignment_score !== null ? (float) $a->assignment_score : null,
                    ];
                })->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
        ]);
    }
}
