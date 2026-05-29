<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminReportService
{
    public function __construct(protected AdminSemesterReportService $semesterReportService) {}

    /**
     * Dapatkan ID Tahun Ajaran Aktif
     */
    private function getActiveAcademicYearId(): int
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        if (! $activeYear) {
            throw new HttpException(400, 'Tidak ada Tahun Ajaran yang aktif.');
        }

        return $activeYear->id;
    }

    /**
     * Report Kehadiran Global (Dikelompokkan per Kelas)
     */
    public function getAttendanceSummary(?int $academicYearId = null): array
    {
        $yearId = $academicYearId ?? $this->getActiveAcademicYearId();

        $report = DB::table('classes')
            ->leftJoin('schedules', 'classes.id', '=', 'schedules.class_id')
            ->leftJoin('attendances', 'schedules.id', '=', 'attendances.schedule_id')
            ->where('classes.academic_year_id', $yearId)
            ->select(
                'classes.id as class_id',
                'classes.name as class_name',
                DB::raw('COUNT(attendances.id) as total_records'),
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as total_present"),
                DB::raw("SUM(CASE WHEN attendances.status = 'alpa' THEN 1 ELSE 0 END) as total_alpa"),
                DB::raw("SUM(CASE WHEN attendances.status = 'sick' THEN 1 ELSE 0 END) as total_sick"),
                DB::raw("SUM(CASE WHEN attendances.status = 'permission' THEN 1 ELSE 0 END) as total_permission"),
                DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as total_late")
            )
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get();

        return $report->toArray();
    }

    /**
     * Report Akademik Global (Rata-rata Nilai per Kelas)
     */
    public function getAcademicSummary(?int $academicYearId = null): array
    {
        $yearId = $academicYearId ?? $this->getActiveAcademicYearId();

        $report = DB::table('classes')
            ->leftJoin('schedules', 'classes.id', '=', 'schedules.class_id')
            ->leftJoin('assignments', 'schedules.id', '=', 'assignments.schedule_id')
            ->leftJoin('submissions', 'assignments.id', '=', 'submissions.assignment_id')
            ->leftJoin('grades', 'submissions.id', '=', 'grades.submission_id')
            ->where('classes.academic_year_id', $yearId)
            ->select(
                'classes.id as class_id',
                'classes.name as class_name',
                DB::raw('COUNT(DISTINCT assignments.id) as total_assignments'),
                DB::raw('COUNT(grades.id) as total_graded_submissions'),
                DB::raw('ROUND(AVG(grades.score), 2) as average_class_score')
            )
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get();

        return $report->toArray();
    }

    /**
     * Distribusi kesiapan rapor per siswa untuk admin.
     *
     * @return array{is_all_ready:bool,data:array<int, array{student_id:int,nis:string,name:string,class_name:string,is_ready:bool,missing_info:string}>}
     */
    public function getDistributionList(?int $academicYearId = null): array
    {
        $yearId = $academicYearId ?? $this->getActiveAcademicYearId();

        return $this->semesterReportService->getAcademicYearReadiness($yearId);
    }
}
