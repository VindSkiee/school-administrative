<?php

namespace App\Http\Controllers\API\Student;

use App\Models\AcademicYear;
use App\Services\StudentReportService;
use App\Services\ReportValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ReportPdfService;
use App\Services\AdminSemesterReportService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SemesterReportController
{
    public function __construct(
        protected StudentReportService $reportService,
        protected ReportPdfService $pdfService,
        protected ReportValidationService $validationService,
        protected AdminSemesterReportService $adminReportService,
    ) {}

    /**
     * GET /reports/academic-years
     * List academic years the student has classes in (for dropdown)
     */
    public function academicYears(Request $request): JsonResponse
    {
        $student = auth('api')->user()->student;
        if (!$student) {
            return response()->json(['data' => []]);
        }

        $yearIds = $student->classes()->pluck('classes.academic_year_id')->unique();
        $years = AcademicYear::whereIn('id', $yearIds)
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'semester', 'is_active', 'is_report_published']);

        return response()->json(['data' => $years]);
    }

    public function reportStatus(Request $request): JsonResponse
    {
        $yearId = $request->query('academic_year_id');
        $year = $yearId
            ? AcademicYear::find($yearId)
            : AcademicYear::where('is_active', true)->first();

        return response()->json([
            'is_report_published' => $year?->is_report_published ?? false,
            'published_at' => $year?->is_report_published ? $year->updated_at : null,
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $student = auth('api')->user()->student;

        if (!$student || $student->status !== 'active') {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        // Accept optional academic_year_id — defaults to active year
        $yearId = $request->query('academic_year_id');
        $year = $yearId
            ? AcademicYear::find($yearId)
            : AcademicYear::where('is_active', true)->first();

        if (!$year) {
            return response()->json(['error' => 'Tahun ajaran tidak ditemukan.'], 404);
        }

        $class = $student->classes()->where('classes.academic_year_id', $year->id)->first();

        if (!$class) {
            return response()->json(['error' => 'Anda belum terdaftar di kelas pada tahun ajaran ini.'], 403);
        }

        try {
            $report = $this->reportService->getSemesterReport($student->user_id, $class->id);

            return response()->json([
                'success' => true,
                'data' => $report
            ], 200);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function downloadPdf(Request $request)
    {
        $user = auth('api')->user();
        $student = $user->student;

        if (!$student || $student->status !== 'active') {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        try {
            // Accept optional academic_year_id — defaults to active year
            $yearId = $request->query('academic_year_id');
            $year = $yearId
                ? AcademicYear::find($yearId)
                : AcademicYear::where('is_active', true)->first();

            if (! $year) {
                return response()->json(['error' => 'Tahun ajaran tidak ditemukan.'], 400);
            }

            // Check if the selected year's report is published
            if (! $year->is_report_published) {
                return response()->json(['error' => 'Rapor untuk tahun ajaran ini belum diterbitkan.'], 403);
            }

            // Get student's class for this academic year
            $class = $student->classes()
                ->where('classes.academic_year_id', $year->id)
                ->first();

            if (! $class) {
                return response()->json(['error' => 'Anda belum terdaftar di kelas pada tahun ajaran ini.'], 403);
            }

            // Build report data using the admin service (reuses existing logic)
            $reportData = $this->adminReportService->buildReportDataPublic($year, $student, $class);

            // Generate PDF
            return $this->pdfService->generateSemesterReportPdf($reportData, $user->name);

        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal mengunduh rapor: ' . $e->getMessage()], 500);
        }
    }
}
