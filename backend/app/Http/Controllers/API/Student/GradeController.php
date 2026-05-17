<?php

namespace App\Http\Controllers\API\Student;

use App\Models\Submission;
use Illuminate\Http\JsonResponse;

class GradeController
{
    public function index(): JsonResponse
    {
        $studentId = auth('api')->user()->user_id;

        // Ambil semua submission siswa ini yang sudah dinilai (Has('grade'))
        $gradedSubmissions = Submission::query()
            ->with([
                'assignment.schedule.subject', 
                'assignment.schedule.teacher.user', 
                'grade'
            ])
            ->where('student_id', $studentId)
            ->whereHas('grade') // Hanya tampilkan yang sudah ada nilainya
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($gradedSubmissions);
    }
}