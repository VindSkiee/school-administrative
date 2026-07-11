<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class ReportPdfService
{
    /**
     * Generate PDF based on report data
     */
    public function generateSemesterReportPdf(array $reportData, string $studentName)
    {
        $pdf = Pdf::loadView('reports.semester', ['data' => $reportData]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['enable_php' => true]);

        $semesterLabel = $reportData['semester_label'] ?? $reportData['semester'];
        $fileName = sprintf(
            'Rapor_%s_%s_%s.pdf',
            $semesterLabel,
            str_replace('/', '-', $reportData['academic_year']),
            preg_replace('/[^A-Za-z0-9\-]/', '_', $studentName)
        );

        $pdf->render();

        $canvas = $pdf->getDomPDF()->get_canvas();
        $fontMetrics = $pdf->getDomPDF()->getFontMetrics();

        $className = $reportData['class_name'] ?? '';
        $studentFullName = $reportData['student_name'] ?? '';
        $nis = $reportData['student_nis'] ?? $reportData['student_nisn'] ?? '';

        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($className, $studentFullName, $nis) {
            $font = $fontMetrics->get_font('helvetica');
            $size = 9;
            $color = [0, 0, 0];
            $w = $canvas->get_width();
            $h = $canvas->get_height();

            $leftMargin = 95;
            $rightMargin = $w - 70;
            $lineY = $h - 55;
            $textY = $h - 47;

            $canvas->line($leftMargin, $lineY, $rightMargin, $lineY, $color, 0.5);

            $leftText = "Kelas {$className} | {$studentFullName} | {$nis}";
            $canvas->text($leftMargin, $textY, $leftText, $font, $size, $color);

            $rightText = "Halaman : {$pageNumber}";
            $textWidth = $fontMetrics->get_text_width($rightText, $font, $size);
            $canvas->text($rightMargin - $textWidth, $textY, $rightText, $font, $size, $color);
        });

        return $pdf->download($fileName);
    }
}
