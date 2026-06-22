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

    // Ganti index untuk menerima schedule_id
    public function index(string $scheduleId): \Illuminate\Http\JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        $assignments = \App\Models\Assignment::withCount('submissions')
            ->where('schedule_id', $scheduleId)
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('created_at', 'desc')
            ->get(); // Ambil semua untuk kelas ini agar cross-week grading mudah

        return response()->json($assignments);
    }

    // BARU: Ambil semua tugas dari semua kelas untuk halaman Dashboard Global
    // PERF FIX: added pagination to prevent loading all assignments unbounded
    public function globalIndex(Request $request): \Illuminate\Http\JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $perPage = min((int) $request->query('per_page', 20), 100);

        $assignments = \App\Models\Assignment::with(['schedule.schoolClass', 'schedule.subject'])
            ->withCount('submissions')
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('due_date', 'desc')
            ->paginate($perPage);

        return response()->json($assignments);
    }

    public function store(\App\Http\Requests\Teacher\StoreAssignmentRequest $request): \Illuminate\Http\JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        try {
            $assignment = $this->assignmentService->createAssignment(
                $teacherId, 
                $request->validated(), 
                $request->file('files')
            );
            return response()->json(['success' => true, 'data' => $assignment], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $assignment = \App\Models\Assignment::findOrFail($id);
        try {
            $this->assignmentService->deleteAssignment($teacherId, $assignment);
            return response()->json(['success' => true, 'message' => 'Tugas dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function submissions(string $id): \Illuminate\Http\JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        
        // Tarik submissions BERSERTA nilainya (grade)
        $assignment = \App\Models\Assignment::with(['submissions.student.user', 'submissions.grade'])->findOrFail($id);

        if ($assignment->schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }
        return response()->json($assignment);
    }
}