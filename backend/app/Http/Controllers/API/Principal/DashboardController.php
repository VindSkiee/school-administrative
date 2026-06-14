<?php

namespace App\Http\Controllers\API\Principal;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
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

    /**
     * GET /dashboard/stats
     * Quick stats for the principal dashboard header cards.
     */
    public function stats(): JsonResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $totalStudents = Student::where('status', 'active')->count();
        $totalTeachers = Teacher::count();
        $totalClasses = $activeYear
            ? SchoolClass::where('academic_year_id', $activeYear->id)->count()
            : 0;

        $activeAcademicYear = $activeYear
            ? "{$activeYear->name} " . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap')
            : 'Belum diatur';

        return response()->json([
            'success' => true,
            'data' => [
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'total_classes' => $totalClasses,
                'active_academic_year' => $activeAcademicYear,
            ],
        ]);
    }
}
