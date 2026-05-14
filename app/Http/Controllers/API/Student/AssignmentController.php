<?php

namespace App\Http\Controllers\API\Student;

use App\Models\Assignment;
use App\Http\Requests\Student\StoreSubmissionRequest;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;

class AssignmentController
{
    public function __construct(protected AssignmentService $assignmentService) {}

    public function index(): JsonResponse
    {
        $student = auth('api')->user()->student;

        if (!$student || $student->status !== 'active' || !$student->class_id) {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        // Ambil tugas beserta data submission milik siswa ini (jika sudah kumpul)
        $assignments = Assignment::with(['schedule.subject', 'schedule.teacher.user'])
            ->with(['submissions' => function ($query) use ($student) {
                $query->where('student_id', $student->user_id);
            }])
            ->whereHas('schedule', function ($query) use ($student) {
                $query->where('class_id', $student->class_id);
            })
            ->orderBy('due_date', 'asc')
            ->paginate(15);

        return response()->json($assignments);
    }

    public function submit(StoreSubmissionRequest $request, string $id): JsonResponse
    {
        $student = auth('api')->user()->student;

        try {
            $submission = $this->assignmentService->submitAssignment(
                $student->user_id,
                $student->class_id,
                $id,
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Jawaban tugas berhasil diunggah.',
                'data' => $submission
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}