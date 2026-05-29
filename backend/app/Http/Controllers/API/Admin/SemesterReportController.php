<?php

namespace App\Http\Controllers\API\Admin;

use App\Services\AdminSemesterReportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SemesterReportController
{
    public function __construct(protected AdminSemesterReportService $adminReportService) {}

    public function publish(string $academicYearId): JsonResponse
    {
        try {
            $readiness = $this->adminReportService->getAcademicYearReadiness((int) $academicYearId);

            if (! $readiness['is_all_ready']) {
                return response()->json([
                    'error' => 'Tidak dapat mempublikasi, masih ada siswa dengan data tidak lengkap',
                ], 422);
            }

            $academicYear = $this->adminReportService->publishReport((int) $academicYearId);

            return response()->json([
                'success' => true,
                'message' => 'Rapor untuk semester ini resmi diterbitkan. Akses edit nilai guru telah dikunci.',
                'data' => $academicYear,
            ], 200);
        } catch (HttpException $exception) {
            return $this->formatHttpExceptionResponse($exception);
        }
    }

    // Method baru untuk Admin Download PDF Siswa
    public function downloadStudentPdf(string $academicYearId, string $studentId)
    {
        try {
            $readiness = $this->adminReportService->getStudentReadiness((int) $academicYearId, (int) $studentId);

            if (! $readiness['is_ready']) {
                return response()->json(['error' => 'Data akademik belum lengkap'], 422);
            }

            return $this->adminReportService->downloadStudentPdf((int) $academicYearId, (int) $studentId);
        } catch (HttpException $exception) {
            return $this->formatHttpExceptionResponse($exception);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh PDF: '.$exception->getMessage(),
            ], 500);
        }
    }

    private function formatHttpExceptionResponse(HttpException $exception): JsonResponse
    {
        $decodedMessage = json_decode($exception->getMessage(), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMessage)) {
            return response()->json($decodedMessage, $exception->getStatusCode());
        }

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], $exception->getStatusCode());
    }
}
