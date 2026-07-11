<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreHolidayRequest;
use App\Models\Holiday;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HolidayController
{
    public function __construct(protected HolidayService $holidayService) {}

    public function index(): JsonResponse
    {
        $holidays = Holiday::query()
            ->orderBy('date')
            ->get();

        return response()->json($holidays);
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        try {
            $holiday = $this->holidayService->createHoliday($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil ditetapkan. Sesi pertemuan yang terdampak telah diperbarui.',
                'data' => $holiday,
            ], 201);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $holiday = Holiday::query()->findOrFail($id);

        try {
            $this->holidayService->deleteHoliday($holiday);

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil dihapus. Sesi pertemuan yang terdampak telah dipulihkan.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
