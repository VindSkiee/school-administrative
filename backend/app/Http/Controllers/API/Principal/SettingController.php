<?php

namespace App\Http\Controllers\API\Principal;

use App\Models\AcademicYear;
use App\Models\GradingSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController
{
    /**
     * GET /settings/grading
     * Return grading settings for the currently active academic year.
     */
    public function getGrading(): JsonResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (! $activeYear) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada tahun ajaran aktif.',
            ], 400);
        }

        $setting = GradingSetting::where('academic_year_id', $activeYear->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'academic_year_id' => $activeYear->id,
                'academic_year_name' => "{$activeYear->name} " . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap'),
                'task_weight' => $setting?->task_weight ?? 40,
                'uts_weight' => $setting?->uts_weight ?? 25,
                'uas_weight' => $setting?->uas_weight ?? 25,
                'attendance_weight' => $setting?->attendance_weight ?? 10,
            ],
        ]);
    }

    /**
     * PUT /settings/grading
     * Update grading settings for the currently active academic year.
     */
    public function updateGrading(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'uts_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'uas_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'attendance_weight' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $total = $validated['task_weight'] + $validated['uts_weight'] + $validated['uas_weight'] + $validated['attendance_weight'];

        if ($total !== 100) {
            return response()->json([
                'success' => false,
                'message' => "Total bobot keseluruhan (Tugas, UTS, UAS, dan Kehadiran) harus tepat 100%. Saat ini totalnya {$total}%.",
                'errors' => [
                    'weights' => ["Total bobot harus 100%, saat ini {$total}%."],
                ],
            ], 422);
        }

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (! $activeYear) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada tahun ajaran aktif.',
            ], 400);
        }

        $setting = GradingSetting::updateOrCreate(
            ['academic_year_id' => $activeYear->id],
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
        ]);
    }
}
