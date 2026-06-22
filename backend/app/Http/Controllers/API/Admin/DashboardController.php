<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    public function index(): JsonResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        // PERF FIX: single grouped COUNT query instead of 3 separate queries
        // Also cached for 60 seconds to reduce DB load on repeated dashboard visits
        $stats = Cache::remember('admin_dashboard_stats', 60, function () {
            $roleCounts = DB::table('users')
                ->select('role', DB::raw('COUNT(*) as total'))
                ->whereIn('role', ['student', 'teacher'])
                ->groupBy('role')
                ->pluck('total', 'role');

            return [
                'students' => $roleCounts->get('student', 0),
                'teachers' => $roleCounts->get('teacher', 0),
            ];
        });

        $totalClasses = $activeYear
            ? SchoolClass::where('academic_year_id', $activeYear->id)->count()
            : 0;

        // Ambil 3 aktivitas terakhir untuk widget pengawasan
        $recentActivities = ActivityLog::with('user:id,name,role')
            ->latest()
            ->take(3)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'academic_year' => $activeYear ? $activeYear->name . ' (' . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap') . ')' : 'Tidak ada yang aktif',
                'stats' => [
                    'students' => $stats['students'],
                    'teachers' => $stats['teachers'],
                    'classes'  => $totalClasses,
                ],
                'recent_activities' => $recentActivities
            ]
        ]);
    }
}