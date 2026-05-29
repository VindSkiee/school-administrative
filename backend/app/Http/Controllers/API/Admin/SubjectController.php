<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController
{
    public function index(Request $request): JsonResponse
    {
        // Pagination ringan dengan pencarian nama
        $query = Subject::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('code', 'like', '%'.$request->search.'%');
        }

        $perPage = (int) $request->query('per_page', 100);
        $perPage = max(1, min($perPage, 100));
        $subjects = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($subjects);
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = Subject::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil ditambahkan.',
            'data' => $subject,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $subject = Subject::findOrFail($id);

        return response()->json($subject);
    }

    public function update(StoreSubjectRequest $request, string $id): JsonResponse
    {
        $subject = Subject::findOrFail($id);
        $subject->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil diperbarui.',
            'data' => $subject,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $subject = Subject::findOrFail($id);

        // 🛡️ Mencegah penghapusan jika mapel sudah masuk jadwal
        if ($subject->schedules()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus mata pelajaran ini karena sudah terikat dengan jadwal kelas aktif.',
            ], 403);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil dihapus.',
        ]);
    }
}
