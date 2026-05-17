<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\Schedule;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScheduleController
{
    public function __construct(protected ScheduleService $scheduleService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Schedule::with(['schoolClass', 'subject', 'teacher.user', 'academicYear']);

        // Filter dinamis (contoh: Admin ingin melihat jadwal kelas X-IPA-1 saja)
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $schedules = $query->orderBy('day_of_week')->orderBy('start_time')->paginate(20);

        return response()->json($schedules);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        try {
            $schedule = $this->scheduleService->createSchedule($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dibuat.',
                'data' => $schedule->load(['schoolClass', 'subject', 'teacher.user'])
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function show(string $id): JsonResponse
    {
        $schedule = Schedule::with(['schoolClass', 'subject', 'teacher.user', 'academicYear'])->findOrFail($id);
        return response()->json($schedule);
    }

    public function update(StoreScheduleRequest $request, string $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        try {
            $updatedSchedule = $this->scheduleService->updateSchedule($schedule, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diperbarui.',
                'data' => $updatedSchedule
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        // Proteksi: Jadwal tidak bisa dihapus jika sudah ada absensi atau tugas terkait
        if ($schedule->attendances()->exists() || $schedule->assignments()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus jadwal ini karena sudah memiliki data absensi atau tugas berjalan.'
            ], 403);
        }

        $schedule->delete();

        return response()->json(['success' => true, 'message' => 'Jadwal berhasil dihapus.']);
    }
}