<?php

namespace App\Http\Controllers\API\Principal;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\PrincipalDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * GET /dashboard/yoy
     * Year-over-Year School Performance Index (last 5 academic years).
     */
    public function yoy(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getYearOverYearIndex(),
        ]);
    }

    /**
     * GET /dashboard/grade-distribution
     * Current grade distribution by category (A/B/C/D).
     */
    public function gradeDistribution(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getGradeDistribution(),
        ]);
    }

    /**
     * GET /dashboard/academic-years
     * Returns only academic years where BOTH odd AND even semesters exist
     * and BOTH have is_report_published=true (for cohort dropdown).
     *
     * Logic: Group by name (e.g., "2025/2026"), check that the group contains
     * at least one 'odd' + one 'even' record, and both are published.
     */
    public function academicYears(): JsonResponse
    {
        $allYears = AcademicYear::orderBy('id', 'desc')
            ->get(['id', 'name', 'semester', 'is_active', 'is_report_published']);

        // Group by year name
        $grouped = $allYears->groupBy('name');

        $combinedYears = [];
        foreach ($grouped as $name => $years) {
            $odd = $years->firstWhere('semester', 'odd');
            $even = $years->firstWhere('semester', 'even');

            // Only include if BOTH semesters exist AND both are published
            if ($odd && $even && $odd->is_report_published && $even->is_report_published) {
                // Use the first (most recent) ID as the representative
                $representativeId = $years->first()->id;
                $combinedYears[] = [
                    'id' => $representativeId,
                    'name' => $name,
                    'is_combined' => true,
                    'odd_year_id' => $odd->id,
                    'even_year_id' => $even->id,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $combinedYears,
        ]);
    }

    /**
     * GET /dashboard/curriculum-trend?grade_level=7&limit=5
     *
     * Curriculum Trend: evaluates a specific grade level's average performance index
     * across the last N published academic years, with demographic data.
     *
     * Required indexes for scale:
     *   - idx_class_student_ay_class  ON class_student(academic_year_id, class_id)
     *   - idx_schedules_ay_class      ON schedules(academic_year_id, class_id)
     *   - idx_grades_submission_score ON grades(submission_id, score)
     *   - idx_submissions_assignment  ON submissions(assignment_id, student_id)
     *   - idx_assignments_schedule    ON assignments(schedule_id, type)
     *   - idx_students_status         ON students(status)
     */
    public function curriculumTrend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grade_level' => 'required|integer|in:7,8,9',
            'limit'       => 'sometimes|integer|min:1|max:10',
        ]);

        $gradeLevel = (int) $validated['grade_level'];
        $limit = (int) ($validated['limit'] ?? 5);

        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getCurriculumTrend($gradeLevel, $limit),
        ]);
    }

    /**
     * GET /dashboard/cohort-trend?entry_year_id=3 OR ?entry_year_ids[]=3&entry_year_ids[]=4
     *
     * Cohort Trend: tracks the cognitive growth of a student batch as they
     * progress through grade levels (7 → 8 → 9).
     *
     * Supports combined years: pass entry_year_ids[] array to include students
     * from both odd and even semesters of the same academic year.
     *
     * Required indexes for scale:
     *   - idx_class_student_student ON class_student(student_id)
     *   - idx_class_student_student_ay ON class_student(student_id, academic_year_id)
     */
    public function cohortTrend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_year_id'   => 'required_without:entry_year_ids|integer|exists:academic_years,id',
            'entry_year_ids'  => 'required_without:entry_year_id|array|min:1|max:2',
            'entry_year_ids.*' => 'integer|exists:academic_years,id',
        ]);

        // Support both single ID and array of IDs (combined years)
        $entryYearIds = isset($validated['entry_year_ids'])
            ? array_map('intval', $validated['entry_year_ids'])
            : [(int) $validated['entry_year_id']];

        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getCohortTrend($entryYearIds),
        ]);
    }
}
