<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class ReportPdfService
{
    /**
     * Build PDF instance from report data (shared logic).
     */
    private function buildPdf(array $reportData): \Barryvdh\DomPDF\PDF
    {
        gc_collect_cycles();

        $pdf = Pdf::loadView('reports.semester', ['data' => $reportData]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'enable_php' => false,
            'isHtml5ParserEnabled' => false,
            'isRemoteEnabled' => false,
            'isJavascriptEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'defaultFont' => 'helvetica',
        ]);

        $pdf->render();

        gc_collect_cycles();

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

        return $pdf;
    }

    /**
     * Generate a unique file name for the report PDF.
     */
    private function buildFileName(array $reportData, string $studentName): string
    {
        $semesterLabel = $reportData['semester_label'] ?? $reportData['semester'];

        return sprintf(
            'Rapor_%s_%s_%s.pdf',
            $semesterLabel,
            str_replace('/', '-', $reportData['academic_year']),
            preg_replace('/[^A-Za-z0-9\-]/', '_', $studentName)
        );
    }

    /**
     * Generate PDF and return as download response.
     */
    public function generateSemesterReportPdf(array $reportData, string $studentName)
    {
        $pdf = $this->buildPdf($reportData);
        $fileName = $this->buildFileName($reportData, $studentName);

        $response = $pdf->download($fileName);

        gc_collect_cycles();

        return $response;
    }
}
