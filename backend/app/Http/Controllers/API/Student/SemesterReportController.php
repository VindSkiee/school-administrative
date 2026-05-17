<?php

namespace App\Http\Controllers\API\Student;

use App\Services\StudentReportService;
use Illuminate\Http\JsonResponse;
use App\Services\ReportPdfService;

class SemesterReportController
{
    public function __construct(
        protected StudentReportService $reportService,
        protected ReportPdfService $pdfService
    ) {}

    public function show(): JsonResponse
    {
        $student = auth('api')->user()->student;

        if (!$student || $student->status !== 'active' || !$student->class_id) {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        try {
            $report = $this->reportService->getSemesterReport($student->user_id, $student->class_id);

            return response()->json([
                'success' => true,
                'data' => $report
            ], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function downloadPdf()
    {
        $user = auth('api')->user();
        $student = $user->student;

        if (!$student || $student->status !== 'active' || !$student->class_id) {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        try {
            // Tarik data menggunakan service yang sama (ini sudah melewati validasi is_report_published)
            $reportData = $this->reportService->getSemesterReport($student->user_id, $student->class_id);

            // Generate dan return file PDF
            return $this->pdfService->generateSemesterReportPdf($reportData, $user->name);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}