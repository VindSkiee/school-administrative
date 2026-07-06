<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Requests\Teacher\StoreAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController
{
    public function __construct(protected AssignmentService $assignmentService) {}

    // Ganti index untuk menerima schedule_id
    public function index(string $scheduleId): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        $assignments = Assignment::with([
            'schedule.subject.competencySettings' => fn ($q) => $q->where('academic_year_id', $activeYearId),
        ])->withCount('submissions')
            ->where('schedule_id', $scheduleId)
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($assignments);
    }

    // BARU: Ambil semua tugas dari semua kelas untuk halaman Dashboard Global
    // PERF FIX: added pagination to prevent loading all assignments unbounded
    public function globalIndex(Request $request): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $perPage = min((int) $request->query('per_page', 20), 100);

        $assignments = Assignment::with([
            'schedule.schoolClass',
            'schedule.subject' => fn ($q) => $q->with(['competencySettings' => fn ($cq) => $cq->where('academic_year_id', $activeYearId)]),
        ])->withCount('submissions')
            ->withCount(['submissions as submissions_graded_count' => fn ($q) => $q->whereHas('grade')])
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('due_date', 'desc')
            ->paginate($perPage);

        return response()->json($assignments);
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
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

    public function destroy(string $id): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $assignment = Assignment::findOrFail($id);
        try {
            $this->assignmentService->deleteAssignment($teacherId, $assignment);

            return response()->json(['success' => true, 'message' => 'Tugas dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function submissions(string $id): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        $assignment = Assignment::with([
            'submissions.student.user',
            'submissions.grade',
            'schedule',
            'schedule.subject.competencySettings' => fn ($q) => $q->where('academic_year_id', $activeYearId),
        ])->findOrFail($id);

        if ($assignment->schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        return response()->json($assignment);
    }

    /**
     * GET /assignments/{id}/below-kkm
     * Return students whose grade for this exam is below KKM.
     */
    public function belowKKM(string $id): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        try {
            $students = $this->assignmentService->getStudentsBelowKKM($teacherId, (int) $id);

            return response()->json(['success' => true, 'data' => $students]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * POST /assignments/{id}/create-remedial
     * Create remedial assignment for selected students below KKM.
     */
    public function createRemedial(Request $request, string $id): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:students,user_id'],
            'remedial_mode' => ['nullable', 'string', 'in:replace,average,custom'],
        ]);

        try {
            $remedial = $this->assignmentService->createRemedialAssignments(
                $teacherId,
                (int) $id,
                $validated['student_ids'],
                $validated['remedial_mode'] ?? null,
            );

            return response()->json(['success' => true, 'data' => $remedial], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
