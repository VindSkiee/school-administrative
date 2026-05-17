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

    public function index(Request $request): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        $materials = Material::with('schedule.schoolClass', 'schedule.subject')
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($materials);
    }

    public function store(StoreMaterialRequest $request): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        try {
            $material = $this->materialService->uploadMaterial(
                $teacherId, 
                $request->validated(), 
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Materi berhasil diunggah.',
                'data' => $material
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
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