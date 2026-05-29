<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController
{
    public function __construct(protected AcademicYearService $academicYearService) {}

    public function index(Request $request): JsonResponse
    {
        $query = AcademicYear::orderBy('id', 'desc');

        if ($request->query('per_page') === 'all') {
            return response()->json([
                'data' => $query->get(),
            ]);
        }

        $perPage = (int) $request->query('per_page', 100);
        $perPage = max(1, min($perPage, 100));
        $academicYears = $query->paginate($perPage);

        return response()->json($academicYears);
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $academicYear = AcademicYear::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil dibuat.',
            'data' => $academicYear,
        ], 201);
    }

    public function update(StoreAcademicYearRequest $request, string $id): JsonResponse
    {
        $academicYear = AcademicYear::findOrFail($id);
        $academicYear->update($request->validated());

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

        // Cek apakah ada kelas yang terhubung
        if ($academicYear->classes()->exists()) {
            return response()->json(['error' => 'Tidak dapat menghapus tahun ajaran karena masih memiliki kelas yang terdaftar.'], 403);
        }

        $academicYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil dihapus.',
        ]);
    }
}
