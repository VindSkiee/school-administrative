<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Requests\Teacher\GradeEskulRequest;
use App\Services\TeacherEskulService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherEskulController
{
    public function __construct(protected TeacherEskulService $teacherEskulService) {}

    public function assignedEskuls(): JsonResponse
    {
        $teacherId = auth('api')->id();
        $eskuls = $this->teacherEskulService->getAssignedEskuls($teacherId);

        return response()->json(['data' => $eskuls]);
    }

    public function students(Request $request): JsonResponse
    {
        $teacherId = auth('api')->id();
        $eskulId = $request->query('eskul_id') ? (int) $request->query('eskul_id') : null;
        $classId = $request->query('class_id') ? (int) $request->query('class_id') : null;

        $students = $this->teacherEskulService->getStudentsByEskul($teacherId, $eskulId, $classId);

        return response()->json(['data' => $students]);
    }

    public function grade(GradeEskulRequest $request): JsonResponse
    {
        $teacherId = auth('api')->id();
        $gradedCount = $this->teacherEskulService->gradeStudents($teacherId, $request->validated()['grades']);

        return response()->json([
            'success' => true,
            'message' => "{$gradedCount} penilaian eskul berhasil disimpan.",
        ]);
    }
}
