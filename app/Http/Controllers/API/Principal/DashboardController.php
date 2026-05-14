<?php

namespace App\Http\Controllers\API\Principal;

use App\Services\PrincipalDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function __construct(protected PrincipalDashboardService $dashboardService) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getOverviewMetrics(),
        ]);
    }

    public function attendanceTrends(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getAttendanceTrends(),
        ]);
    }

    public function academicPerformance(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getAcademicPerformance(),
        ]);
    }
}
