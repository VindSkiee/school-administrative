<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PrincipalDashboardService
{
    private function getActiveAcademicYearId(): int
    {
        // Cache ID Tahun Ajaran aktif selama 24 jam karena jarang sekali berubah
        return Cache::remember('active_academic_year_id', now()->addHours(24), function () {
            $activeYear = AcademicYear::query()->where('is_active', true)->first();
            if (!$activeYear) {
                throw new HttpException(400, "Sistem belum memiliki Tahun Ajaran yang aktif.");
            }
            return $activeYear->id;
        });
    }

    /**
     * 1. Overview Metrik Sekolah
     */
    public function getOverviewMetrics(): array
    {
        $yearId = $this->getActiveAcademicYearId();

        return Cache::remember('principal_overview_metrics', now()->addHours(24), function () use ($yearId) {
            return [
                // Gunakan ->query() di semua panggilan statis Eloquent
                'total_active_students' => Student::query()->where('status', 'active')->count('*'),
                'total_teachers' => Teacher::query()->count('*'),
                'total_active_classes' => SchoolClass::query()->where('academic_year_id', $yearId)->count('*'),
            ];
        });
    }

    /**
     * 2. Tren Kehadiran Global (Persentase)
     */
    public function getAttendanceTrends(): array
    {
        $yearId = $this->getActiveAcademicYearId();

        // DB facade biasanya aman dari Intelephense, biarkan seperti ini
        $stats = DB::table('attendances')
            ->join('schedules', 'attendances.schedule_id', '=', 'schedules.id')
            ->join('classes', 'schedules.class_id', '=', 'classes.id')
            ->where('classes.academic_year_id', $yearId)
            ->select(
                DB::raw('COUNT(attendances.id) as total_attendance_records'),
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN attendances.status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN attendances.status = 'permission' THEN 1 ELSE 0 END) as permission"),
                DB::raw("SUM(CASE WHEN attendances.status = 'alpa' THEN 1 ELSE 0 END) as alpa"),
                DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late")
            )
            ->first();

        // Kalkulasi Persentase jika ada data
        $total = $stats->total_attendance_records ?: 1; // Cegah division by zero

        return Cache::remember('principal_attendance_trends', now()->addHours(24), function () use ($stats, $total) {
            return [
                'raw_data' => $stats,
                'percentages' => [
                    'present' => round(($stats->present / $total) * 100, 2).'%',
                    'sick' => round(($stats->sick / $total) * 100, 2).'%',
                    'permission' => round(($stats->permission / $total) * 100, 2).'%',
                    'alpa' => round(($stats->alpa / $total) * 100, 2).'%',
                    'late' => round(($stats->late / $total) * 100, 2).'%',
                ],
            ];
        });
    }

    /**
     * 3. Performa Akademik (Top Kelas Berdasarkan Rata-rata)
     */
    public function getAcademicPerformance(): array
    {
        $yearId = $this->getActiveAcademicYearId();

        // Query berat ini di-cache selama 1 jam. 
        // Kepala sekolah tidak butuh data yang real-time per detik, data per 1 jam sudah sangat relevan.
        $cacheKey = "principal_academic_performance_year_{$yearId}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($yearId) {
            $performance = DB::table('classes')
                ->leftJoin('schedules', 'classes.id', '=', 'schedules.class_id')
                ->leftJoin('assignments', 'schedules.id', '=', 'assignments.schedule_id')
                ->leftJoin('submissions', 'assignments.id', '=', 'submissions.assignment_id')
                ->leftJoin('grades', 'submissions.id', '=', 'grades.submission_id')
                ->where('classes.academic_year_id', $yearId)
                ->select(
                    'classes.id',
                    'classes.name as class_name',
                    DB::raw('ROUND(AVG(grades.score), 2) as average_score')
                )
                ->groupBy('classes.id', 'classes.name')
                ->havingRaw('average_score IS NOT NULL')
                ->orderBy('average_score', 'desc')
                ->take(10)
                ->get();

            return $performance->toArray();
        });
    }
}
