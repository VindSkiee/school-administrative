<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScheduleController
{
    public function __construct(protected ScheduleService $scheduleService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Schedule::with([
            'schoolClass:id,name',
            'subject:id,name',
            'teacher:user_id',
            'teacher.user:id,name',
            'academicYear:id,name,semester',
        ])->withCount([
            'meetingSessions as meeting_total',
            'meetingSessions as meeting_completed' => fn ($q) => $q->whereHas('attendances'),
            'meetingSessions as meeting_holiday' => fn ($q) => $q->where('status', 'holiday'),
            'attendances as attendance_count',
            'assignments as assignment_count',
            'materials as material_count',
        ]);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $perPage = (int) $request->query('per_page', 100);
        $perPage = max(1, min($perPage, 100));
        $schedules = $query->orderBy('day_of_week')->orderBy('start_time')->paginate($perPage);

        $schedules->getCollection()->transform(function ($schedule) {
            $schedule->has_data = $schedule->attendance_count > 0
                || $schedule->assignment_count > 0
                || $schedule->material_count > 0
                || $schedule->meeting_completed > 0;

            return $schedule;
        });

        return response()->json($schedules);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        try {
            $createdSchedules = $this->scheduleService->createSchedule($request->validated());

            // Reload relationships for response
            foreach ($createdSchedules as &$schedule) {
                $schedule->load(['schoolClass', 'subject', 'teacher.user']);
            }

            $message = count($createdSchedules) > 1
                ? count($createdSchedules).' jadwal berhasil dibuat.'
                : 'Jadwal berhasil dibuat.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $createdSchedules,
            ], 201);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function show(string $id): JsonResponse
    {
        $schedule = Schedule::with(['schoolClass', 'subject', 'teacher.user', 'academicYear'])
            ->withCount([
                'meetingSessions as meeting_total',
                'meetingSessions as meeting_completed' => fn ($q) => $q->whereHas('attendances'),
            ])
            ->findOrFail($id);

        return response()->json($schedule);
    }

    public function update(StoreScheduleRequest $request, string $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        try {
            $updatedSchedule = $this->scheduleService->updateSchedule($schedule, $request->validated());
            $updatedSchedule->load(['schoolClass', 'subject', 'teacher.user']);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diperbarui.',
                'data' => $updatedSchedule,
            ]);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        $hasData = $schedule->attendances()->exists()
            || $schedule->assignments()->exists()
            || $schedule->materials()->exists()
            || $schedule->meetingSessions()->whereHas('attendances')->exists();

        if ($hasData) {
            return response()->json([
                'error' => 'Tidak dapat menghapus jadwal ini karena sudah memiliki data absensi, tugas, materi, atau pertemuan yang sudah terisi.',
            ], 403);
        }

        $schedule->delete();

        return response()->json(['success' => true, 'message' => 'Jadwal berhasil dihapus.']);
    }

    public function meetingSessions(string $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        $sessions = $schedule->meetingSessions()
            ->withCount('attendances')
            ->orderBy('meeting_number')
            ->get(['id', 'meeting_number', 'date', 'status', 'notes']);

        $completedCount = $sessions->where('attendances_count', '>', 0)->count();

        $stats = [
            'total' => $sessions->count(),
            'scheduled' => $sessions->where('status', 'scheduled')->count(),
            'completed' => $completedCount,
            'holiday' => $sessions->where('status', 'holiday')->count(),
            'canceled' => $sessions->where('status', 'canceled')->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Swap day/time between two schedules.
     */
    public function swap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id_1' => 'required|integer|exists:schedules,id',
            'schedule_id_2' => 'required|integer|exists:schedules,id',
        ]);

        $scheduleA = Schedule::findOrFail($validated['schedule_id_1']);
        $scheduleB = Schedule::findOrFail($validated['schedule_id_2']);

        try {
            [$updatedA, $updatedB] = $this->scheduleService->swapSchedules($scheduleA, $scheduleB);

            $updatedA->load(['schoolClass', 'subject', 'teacher.user']);
            $updatedB->load(['schoolClass', 'subject', 'teacher.user']);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil ditukar.',
                'data' => [
                    'schedule_a' => $updatedA,
                    'schedule_b' => $updatedB,
                ],
            ]);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
