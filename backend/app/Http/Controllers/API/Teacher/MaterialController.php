<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\Material;
use App\Http\Requests\Teacher\StoreMaterialRequest;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController
{
    public function __construct(protected MaterialService $materialService) {}

    // Ubah parameter index untuk menerima schedule_id dari URL
    public function index(Request $request, string $scheduleId): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $date = $request->query('date');

        $materials = \App\Models\Material::with('schedule.schoolClass', 'schedule.subject')
            ->where('schedule_id', $scheduleId)
            ->where('date', $date)
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('created_at', 'desc')
            ->get(); // Gunakan get() karena ini per pertemuan, datanya tidak akan butuh paginate

        return response()->json($materials);
    }

    public function store(StoreMaterialRequest $request): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        try {
            $material = $this->materialService->uploadMaterial(
                $teacherId, 
                $request->validated(), 
                $request->file('files') // <-- Ambil array files
            );

            return response()->json([
                'success' => true,
                'message' => 'Materi berhasil diunggah.',
                'data' => $material
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $material = Material::findOrFail($id);

        try {
            $this->materialService->deleteMaterial($teacherId, $material);
            return response()->json(['success' => true, 'message' => 'Materi berhasil dihapus.']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}