<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Requests\Student\StoreEskulChangeRequest;
use App\Http\Requests\Student\StoreEskulSelectionRequest;
use App\Services\StudentEskulService;
use Illuminate\Http\JsonResponse;

class EskulController
{
    public function __construct(protected StudentEskulService $studentEskulService) {}

    public function options(): JsonResponse
    {
        $options = $this->studentEskulService->getActiveOptions();

        return response()->json(['data' => $options]);
    }

    public function store(StoreEskulSelectionRequest $request): JsonResponse
    {
        $studentId = auth('api')->id();
        $this->studentEskulService->submitSelection($studentId, $request->validated()['eskul_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Pilihan ekstrakurikuler berhasil disimpan.',
        ]);
    }

    public function myEskuls(): JsonResponse
    {
        $studentId = auth('api')->id();
        $eskuls = $this->studentEskulService->getMyEskuls($studentId);

        return response()->json(['data' => $eskuls]);
    }

    public function skip(): JsonResponse
    {
        $studentId = auth('api')->id();
        $this->studentEskulService->skipSelection($studentId);

        return response()->json([
            'success' => true,
            'message' => 'Pilihan eskul dilewati.',
        ]);
    }

    public function getDeadline(): JsonResponse
    {
        $deadline = $this->studentEskulService->getDeadline();

        return response()->json(['data' => $deadline]);
    }

    public function submitChangeRequest(StoreEskulChangeRequest $request): JsonResponse
    {
        $studentId = auth('api')->id();
        $this->studentEskulService->submitChangeRequest($studentId, $request->validated()['eskul_id']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan pergantian eskul berhasil dikirim. Pergantian akan aktif pada semester berikutnya.',
        ]);
    }

    public function cancelChangeRequest(): JsonResponse
    {
        $studentId = auth('api')->id();
        $this->studentEskulService->cancelChangeRequest($studentId);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan pergantian eskul berhasil dibatalkan.',
        ]);
    }
}
