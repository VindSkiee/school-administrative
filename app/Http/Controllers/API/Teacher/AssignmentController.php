<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\Assignment;
use App\Http\Requests\Teacher\StoreAssignmentRequest;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController
{
    public function __construct(protected AssignmentService $assignmentService) {}

    public function index(): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        $assignments = Assignment::with(['schedule.schoolClass', 'schedule.subject'])
            ->withCount('submissions') // Hitung berapa siswa yang sudah kumpul
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('due_date', 'desc')
            ->paginate(15);

        return response()->json($assignments);
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        try {
            $assignment = $this->assignmentService->createAssignment(
                $teacherId, 
                $request->validated(), 
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Tugas berhasil dibuat.',
                'data' => $assignment
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function submissions(string $id): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $assignment = Assignment::with(['submissions.student.user'])->findOrFail($id);

        // Otorisasi: Pastikan ini tugas miliknya
        $schedule = $assignment->schedule;
        if ($schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        return response()->json($assignment);
    }
}