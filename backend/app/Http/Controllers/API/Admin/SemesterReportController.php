<?php

namespace App\Http\Controllers\API\Admin;

use App\Services\AdminSemesterReportService;
use Illuminate\Http\JsonResponse;
use App\Services\GradeAggregationService;
use App\Services\ReportPdfService;
use App\Models\AcademicYear;
use App\Models\Student;

class SemesterReportController
{
    public function __construct(
        protected AdminSemesterReportService $adminReportService,
        protected GradeAggregationService $aggregationService,
        protected ReportPdfService $pdfService
    ) {}

    public function publish(string $academicYearId): JsonResponse
    {
        try {
            $academicYear = $this->adminReportService->publishReport((int) $academicYearId);

            return response()->json([
                'success' => true,
                'message' => 'Rapor untuk semester ini resmi diterbitkan. Akses edit nilai guru telah dikunci.',
                'data' => $academicYear
            ], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    // Method baru untuk Admin Download PDF Siswa
    public function downloadStudentPdf(string $academicYearId, string $studentId)
    {
        try {
            $academicYear = AcademicYear::query()->findOrFail($academicYearId);
            $student = Student::query()->with('user')->findOrFail($studentId);

            // Admin bisa download meskipun status is_report_published masih false (untuk review/draft)
            $grades = $this->aggregationService->getStudentAggregate($student->user_id, $student->class_id);

            $reportData = [
                'student_id' => $student->user_id,
                'academic_year' => $academicYear->name,
                'semester' => $academicYear->semester,
                'published_at' => $academicYear->updated_at,
                'results' => $grades
            ];

            return $this->pdfService->generateSemesterReportPdf($reportData, $student->user->name);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengunduh PDF: ' . $e->getMessage()], 500);
        }
    }
}