<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AcademicYearController
{
    public function __construct(protected AcademicYearService $academicYearService) {}

    public function index(Request $request): JsonResponse
    {
        // PERF FIX: cache as plain array (not Eloquent Collection) to avoid serialization issues
        $academicYears = Cache::remember('admin_academic_years_list', 120, function () {
            return AcademicYear::orderBy('id', 'desc')->get()->toArray();
        });

        // PERF FIX: cap 'all' at 100 max to prevent unbounded responses
        if ($request->query('per_page') === 'all') {
            $perPage = 100;
        } else {
            $perPage = (int) $request->query('per_page', 100);
            $perPage = max(1, min($perPage, 100));
        }

        // Paginate from plain array
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
        Cache::forget('admin_academic_years_list'); // Invalidate cache

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

        // Cek apakah ada kelas yang terhubung
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
}
