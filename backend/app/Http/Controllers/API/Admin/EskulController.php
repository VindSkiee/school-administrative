<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreEskulRequest;
use App\Http\Requests\Admin\UpdateEskulRequest;
use App\Services\EskulService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EskulController
{
    public function __construct(protected EskulService $eskulService) {}

    public function index(Request $request): JsonResponse
    {
        $eskuls = $this->eskulService->getAll();

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $eskuls = array_filter($eskuls, fn ($e) => str_contains(strtolower($e['name']), $search));
        }

        return response()->json(['data' => array_values($eskuls)]);
    }

    public function store(StoreEskulRequest $request): JsonResponse
    {
        $eskul = $this->eskulService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ekstrakurikuler berhasil ditambahkan.',
            'data' => $eskul,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $eskul = $this->eskulService->getById((int) $id);

        return response()->json(['data' => $eskul]);
    }

    public function update(UpdateEskulRequest $request, string $id): JsonResponse
    {
        $eskul = $this->eskulService->update((int) $id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ekstrakurikuler berhasil diperbarui.',
            'data' => $eskul,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->eskulService->delete((int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Ekstrakurikuler berhasil dihapus.',
        ]);
    }

    public function assignTeacher(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        $eskul = $this->eskulService->assignTeacher((int) $id, $validated['teacher_id'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'PIC Guru berhasil diperbarui.',
            'data' => $eskul,
        ]);
    }
}
