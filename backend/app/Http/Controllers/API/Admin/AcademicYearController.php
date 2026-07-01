<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\MeetingSession;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Services\AcademicYearService;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AcademicYearController
{
    public function __construct(protected AcademicYearService $academicYearService) {}

    public function index(Request $request): JsonResponse
    {
        $academicYears = Cache::remember('admin_academic_years_list', 120, function () {
            return AcademicYear::orderBy('id', 'desc')->get()->toArray();
        });

        if ($request->query('per_page') === 'all') {
            $perPage = 100;
        } else {
            $perPage = (int) $request->query('per_page', 100);
            $perPage = max(1, min($perPage, 100));
        }

        $page = (int) $request->query('page', 1);
        $total = count($academicYears);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($academicYears, $offset, $perPage);

        return response()->json([
            'data' => $slice,
            'total' => $total,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'per_page' => $perPage,
        ]);
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $academicYear = AcademicYear::create($request->validated());
        Cache::forget('admin_academic_years_list');

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil dibuat.',
            'data' => $academicYear,
        ], 201);
    }

    public function update(StoreAcademicYearRequest $request, string $id): JsonResponse
    {
        $academicYear = AcademicYear::findOrFail($id);
        $oldEndDate = $academicYear->end_date?->toDateString();

        $academicYear->update($request->validated());

        $newEndDate = $academicYear->end_date?->toDateString();

        // Jika end_date berubah, regenerate sessions untuk kelas yang belum published
        if ($newEndDate && $newEndDate !== $oldEndDate) {
            $this->regenerateUnpublishedSessions($academicYear);
        }

        Cache::forget('admin_academic_years_list');

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil diperbarui.',
            'data' => $academicYear,
        ]);
    }

    public function setActive(string $id): JsonResponse
    {
        $academicYear = AcademicYear::findOrFail($id);

        try {
            $updatedAcademicYear = $this->academicYearService->setActive($academicYear);
            Cache::forget('admin_academic_years_list');

            return response()->json([
                'success' => true,
                'message' => 'Tahun ajaran berhasil diaktifkan.',
                'data' => $updatedAcademicYear,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengaktifkan tahun ajaran.'], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $academicYear = AcademicYear::findOrFail($id);

        if ($academicYear->is_active) {
            return response()->json(['error' => 'Tidak dapat menghapus tahun ajaran yang sedang aktif.'], 403);
        }

        if ($academicYear->classes()->exists()) {
            return response()->json(['error' => 'Tidak dapat menghapus tahun ajaran karena masih memiliki kelas yang terdaftar.'], 403);
        }

        $academicYear->delete();
        Cache::forget('admin_academic_years_list');

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil dihapus.',
        ]);
    }

    /**
     * Regenerate meeting sessions for all schedules in unpublished classes.
     */
    private function regenerateUnpublishedSessions(AcademicYear $academicYear): void
    {
        $unpublishedClassIds = SchoolClass::where('academic_year_id', $academicYear->id)
            ->where('is_published', false)
            ->pluck('id');

        if ($unpublishedClassIds->isEmpty()) {
            return;
        }

        $schedules = Schedule::whereIn('class_id', $unpublishedClassIds)
            ->where('academic_year_id', $academicYear->id)
            ->get();

        $scheduleService = app(ScheduleService::class);

        foreach ($schedules as $schedule) {
            // Delete uncompleted sessions (no attendance), then regenerate
            MeetingSession::where('schedule_id', $schedule->id)
                ->whereDoesntHave('attendances')
                ->delete();

            $scheduleService->generateMeetingSessions($schedule);
        }
    }
}
