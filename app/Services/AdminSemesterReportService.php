<?php

namespace App\Services;

use App\Models\AcademicYear;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminSemesterReportService
{
    public function publishReport(int $academicYearId): AcademicYear
    {
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);

        if ($academicYear->is_report_published) {
            throw new HttpException(422, "Rapor untuk semester ini sudah diterbitkan sebelumnya.");
        }

        $academicYear->is_report_published = true;
        $academicYear->save();

        return $academicYear;
    }
}