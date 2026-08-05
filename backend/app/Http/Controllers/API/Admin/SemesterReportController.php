<?php

namespace App\Http\Controllers\API\Admin;

use App\Services\AdminSemesterReportService;
use App\Services\ClassReadinessService;
use Illuminate\Http\JsonResponse;

class SemesterReportController
{
    public function __construct(
        protected AdminSemesterReportService $adminReportService,
        protected ClassReadinessService $classReadinessService,
    ) {}

    /**
     * Download student semester report PDF synchronously.
     */
    public function downloadStudentPdf(string $academicYearId, string $studentId)
    {
        try {
            return $this->adminReportService->downloadStudentPdf(
                (int) $academicYearId,
                (int) $studentId,
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PDF: '.$exception->getMessage(),
            ], $exception->getCode() >= 400 && $exception->getCode() < 600 ? $exception->getCode() : 500);
        }
    }

    public function classesReadiness(string $academicYearId): JsonResponse
    {
        try {
            $readiness = $this->classReadinessService->getClassesReadiness((int) $academicYearId);

            return response()->json(['data' => $readiness]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat status kesiapan kelas: '.$exception->getMessage(),
            ], 500);
        }
    }

    public function classReadinessDetail(string $academicYearId, string $classId): JsonResponse
    {
        try {
            $detail = $this->classReadinessService->getClassReadinessDetail((int) $classId, (int) $academicYearId);

            return response()->json(['data' => $detail]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail kesiapan kelas: '.$exception->getMessage(),
            ], 500);
        }
    }

    public function publishClass(string $academicYearId, string $classId): JsonResponse
    {
        try {
            $result = $this->classReadinessService->publishClass((int) $classId, (int) $academicYearId);

            if (! $result['success']) {
                return response()->json(['error' => $result['message']], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mempublikasi kelas: '.$exception->getMessage(),
            ], 500);
        }
    }

    public function publishAllClasses(string $academicYearId): JsonResponse
    {
        try {
            $result = $this->classReadinessService->publishAllReadyClasses((int) $academicYearId);

            $publishedCount = count($result['published']);
            $skippedCount = count($result['skipped']);

            return response()->json([
                'success' => true,
                'message' => "{$publishedCount} kelas berhasil dipublikasikan, {$skippedCount} kelas dilewati.",
                'data' => $result,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mempublikasi kelas: '.$exception->getMessage(),
            ], 500);
        }
    }
}
