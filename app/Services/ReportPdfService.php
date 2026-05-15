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
        // Render blade template dengan data
        $pdf = Pdf::loadView('reports.semester', ['data' => $reportData]);

        // Atur ukuran kertas (misal: A4, portrait)
        $pdf->setPaper('a4', 'portrait');

        // Format nama file: Rapor_Ganjil_2025_Budi.pdf
        $fileName = sprintf(
            'Rapor_%s_%s_%s.pdf', 
            $reportData['semester'], 
            str_replace('/', '-', $reportData['academic_year']),
            preg_replace('/[^A-Za-z0-9\-]/', '_', $studentName)
        );

        return $pdf->download($fileName);
    }
}