<?php

namespace App\Http\Controllers\API\Student;

use App\Models\AcademicYear;
use App\Services\StudentReportService;
use App\Services\ReportValidationService;
use Illuminate\Http\JsonResponse;
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
     * GET /reports/report-status
     * Lightweight check if the active year report is published.
     */
    public function reportStatus(): JsonResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        return response()->json([
            'is_report_published' => $activeYear?->is_report_published ?? false,
            'published_at' => $activeYear?->is_report_published ? $activeYear->updated_at : null,
        ]);
    }

    public function show(): JsonResponse
    {
        $student = auth('api')->user()->student;

        if (!$student || $student->status !== 'active') {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        // Get student's active class via pivot
        $activeYear = AcademicYear::where('is_active', true)->first();
        $class = $activeYear ? $student->classes()->where('classes.academic_year_id', $activeYear->id)->first() : null;

        if (!$class) {
            return response()->json(['error' => 'Anda belum terdaftar di kelas pada tahun ajaran aktif.'], 403);
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

    public function downloadPdf()
    {
        $user = auth('api')->user();
        $student = $user->student;

        if (!$student || $student->status !== 'active') {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        try {
            // 1. Get active academic year
            $activeYear = AcademicYear::where('is_active', true)->first();
            if (! $activeYear) {
                return response()->json(['error' => 'Tidak ada tahun ajaran aktif.'], 400);
            }

            // 2. Get student's class for this academic year
            $class = $student->classes()
                ->where('classes.academic_year_id', $activeYear->id)
                ->first();

            if (! $class) {
                return response()->json(['error' => 'Anda belum terdaftar di kelas pada tahun ajaran aktif.'], 403);
            }

            // 3. Run 3 strict validations
            $this->validationService->checkEligibility($student, $activeYear, $class);

            // 4. Build report data using the admin service (reuses existing logic)
            $reportData = $this->adminReportService->buildReportDataPublic($activeYear, $student, $class);

            // 5. Generate PDF
            return $this->pdfService->generateSemesterReportPdf($reportData, $user->name);

        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal mengunduh rapor: ' . $e->getMessage()], 500);
        }
    }
}
