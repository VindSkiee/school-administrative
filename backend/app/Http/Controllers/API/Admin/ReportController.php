<?php

namespace App\Http\Controllers\API\Admin;

use App\Services\AdminReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController
{
    public function __construct(protected AdminReportService $reportService) {}

    public function attendanceSummary(Request $request): JsonResponse
    {
        try {
            // Admin bisa memfilter tahun ajaran via query parameter (?academic_year_id=1)
            $academicYearId = $request->query('academic_year_id');
            $data = $this->reportService->getAttendanceSummary($academicYearId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function academicSummary(Request $request): JsonResponse
    {
        try {
            $academicYearId = $request->query('academic_year_id');
            $data = $this->reportService->getAcademicSummary($academicYearId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}