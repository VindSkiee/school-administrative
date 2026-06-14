<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SubjectCompetencySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectDetailController
{
    /**
     * GET /subjects/{subjectId}/detail?academic_year_id=...
     * Return subject info, teachers per year, and competency settings.
     */
    public function show(Request $request, string $subjectId): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $subject = Subject::findOrFail($subjectId);
        $academicYearId = (int) $request->query('academic_year_id');

        // Get teachers who teach this subject in the given academic year
        $teachers = Schedule::with(['teacher.user', 'schoolClass'])
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->map(function ($schedule) {
                return [
                    'teacher_id' => $schedule->teacher_id,
                    'teacher_name' => $schedule->teacher?->user?->name ?? '-',
                    'class_name' => $schedule->schoolClass?->name ?? '-',
                ];
            })
            ->unique('teacher_id')
            ->values();

        // Get or prepare competency settings
        $setting = SubjectCompetencySetting::where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        $competency = $setting ? [
            'id' => $setting->id,
            'sangat_baik_min' => $setting->sangat_baik_min,
            'sangat_baik_text' => $setting->sangat_baik_text,
            'baik_min' => $setting->baik_min,
            'baik_text' => $setting->baik_text,
            'kurang_min' => $setting->kurang_min,
            'kurang_text' => $setting->kurang_text,
            'sangat_kurang_min' => $setting->sangat_kurang_min,
            'sangat_kurang_text' => $setting->sangat_kurang_text,
        ] : null;

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'name' => $subject->name,
                ],
                'teachers' => $teachers,
                'competency' => $competency,
            ],
        ]);
    }

    /**
     * PUT /subjects/{subjectId}/competency?academic_year_id=...
     * Save competency settings for a subject + academic year.
     */
    public function saveCompetency(Request $request, string $subjectId): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'sangat_baik_min' => 'required|integer|min:0|max:100',
            'sangat_baik_text' => 'required|string|max:500',
            'baik_min' => 'required|integer|min:0|max:100',
            'baik_text' => 'required|string|max:500',
            'kurang_min' => 'required|integer|min:0|max:100',
            'kurang_text' => 'required|string|max:500',
            'sangat_kurang_min' => 'required|integer|min:0|max:100',
            'sangat_kurang_text' => 'required|string|max:500',
        ]);

        Subject::findOrFail($subjectId);

        $setting = SubjectCompetencySetting::updateOrCreate(
            [
                'subject_id' => $subjectId,
                'academic_year_id' => $validated['academic_year_id'],
            ],
            [
                'sangat_baik_min' => $validated['sangat_baik_min'],
                'sangat_baik_text' => $validated['sangat_baik_text'],
                'baik_min' => $validated['baik_min'],
                'baik_text' => $validated['baik_text'],
                'kurang_min' => $validated['kurang_min'],
                'kurang_text' => $validated['kurang_text'],
                'sangat_kurang_min' => $validated['sangat_kurang_min'],
                'sangat_kurang_text' => $validated['sangat_kurang_text'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan capaian kompetensi berhasil disimpan.',
            'data' => $setting,
        ]);
    }
}
