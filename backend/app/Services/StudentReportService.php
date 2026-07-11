<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudentReportService
{
    public function __construct(protected GradeAggregationService $aggregationService) {}

    public function getSemesterReport(int $studentId, int $classId): array
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        if (! $activeYear) {
            throw new HttpException(400, 'Tidak ada Tahun Ajaran yang aktif.');
        }

        // VALIDASI GERBANG PUBLIKASI — per-class
        $schoolClass = SchoolClass::findOrFail($classId);
        if (! $schoolClass->is_published) {
            throw new HttpException(403, 'Kelas ini belum dipublikasikan oleh pihak sekolah. Harap tunggu pengumuman resmi.');
        }

        // Kalkulasi nilai on-the-fly (Menggunakan service yang dibuat di modul sebelumnya)
        $grades = $this->aggregationService->getStudentAggregate($studentId, $classId);

        return [
            'student_id' => $studentId,
            'academic_year' => $activeYear->name,
            'semester' => $activeYear->semester,
            'published_at' => $schoolClass->published_at,
            'results' => $grades,
        ];
    }
}
