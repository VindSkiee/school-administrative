<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreGradingSettingRequest;
use App\Models\GradingSetting;
use Illuminate\Http\JsonResponse;

class GradingSettingController
{
    /**
     * Upsert grading settings for a given academic year.
     * Creates a new record or updates the existing one based on academic_year_id.
     */
    public function updateOrCreate(StoreGradingSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $setting = GradingSetting::updateOrCreate(
            ['academic_year_id' => $validated['academic_year_id']],
            [
                'task_weight' => $validated['task_weight'],
                'uts_weight' => $validated['uts_weight'],
                'uas_weight' => $validated['uas_weight'],
                'attendance_weight' => $validated['attendance_weight'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan bobot nilai berhasil disimpan.',
            'data' => $setting,
        ], 200);
    }

    /**
     * Show grading settings for a given academic year.
     */
    public function show(int $academicYearId): JsonResponse
    {
        $setting = GradingSetting::where('academic_year_id', $academicYearId)->first();

        if (! $setting) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Pengaturan bobot belum diatur untuk tahun ajaran ini.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }
}
