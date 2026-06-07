<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function index(): JsonResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Hitung statistik inti
        $totalStudents = User::where('role', 'student')->count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalClasses = $activeYear ? SchoolClass::where('academic_year_id', $activeYear->id)->count() : 0;

        // Ambil 5 aktivitas terakhir untuk widget pengawasan
        $recentActivities = ActivityLog::with('user:id,name,role')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'academic_year' => $activeYear ? $activeYear->name . ' (' . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap') . ')' : 'Tidak ada yang aktif',
                'stats' => [
                    'students' => $totalStudents,
                    'teachers' => $totalTeachers,
                    'classes'  => $totalClasses,
                ],
                'recent_activities' => $recentActivities
            ]
        ]);
    }
}