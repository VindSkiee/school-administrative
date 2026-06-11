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

        // 1. Ambil seluruh mata pelajaran yang terikat pada jadwal kelas aktif siswa (Mencegah mapel hilang)
        $subjects = DB::table('schedules')
            ->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
            ->where('schedules.class_id', $activeClass->id)
            ->select('subjects.id', 'subjects.name', 'subjects.code')
            ->distinct()
            ->get();

        $reportData = [];

        foreach ($subjects as $subject) {
            // 2. Cari semua penugasan untuk jadwal/mapel ini beserta nilai siswa tersebut (jika ada)
            $assignments = DB::table('assignments')
                ->join('schedules', 'assignments.schedule_id', '=', 'schedules.id')
                ->leftJoin('submissions', function($join) use ($student) {
                    $join->on('assignments.id', '=', 'submissions.assignment_id')
                         ->where('submissions.student_id', '=', $student->user_id);
                })
                ->leftJoin('grades', 'submissions.id', '=', 'grades.submission_id')
                ->where('schedules.class_id', $activeClass->id)
                ->where('schedules.subject_id', $subject->id)
                ->select(
                    'assignments.id as assignment_id',
                    'assignments.title as assignment_title',
                    'grades.score as assignment_score'
                )
                ->get();

            // 3. Hitung rerata akhir secara dinamis di level PHP (Aman jika belum ada tugas)
            $gradedTasks = $assignments->whereNotNull('assignment_score');
            $finalGrade = $gradedTasks->count() > 0 ? round($gradedTasks->avg('assignment_score'), 2) : null;

            $reportData[] = [
                'subject_id'   => $subject->id,
                'subject_name' => $subject->name,
                'subject_code' => $subject->code,
                'final_grade'  => $finalGrade,
                'total_graded_assignments' => $gradedTasks->count(),
                'details'      => $assignments->map(function($a) {
                    return [
                        'title' => $a->assignment_title,
                        'score' => $a->assignment_score !== null ? (float)$a->assignment_score : '-'
                    ];
                })->toArray()
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $reportData
        ]);
    }
}