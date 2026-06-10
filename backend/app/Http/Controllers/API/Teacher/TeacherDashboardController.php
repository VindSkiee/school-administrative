<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\Teacher;
use App\Models\Schedule;
use App\Models\Assignment;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TeacherDashboardController
{
    public function index(): JsonResponse
    {
        // Asumsi teacher_id merujuk pada id user yang login
        $teacherId = auth('api')->user()->id;
        $today = strtolower(Carbon::today()->englishDayOfWeek);

        // 1. Cek apakah Guru adalah Wali Kelas
        $teacher = Teacher::with('homeroomClass')->where('user_id', $teacherId)->first();
        $homeroomClass = null;
        if ($teacher && $teacher->homeroomClass) {
            $homeroomClass = [
                'id' => $teacher->homeroomClass->id,
                'name' => $teacher->homeroomClass->name,
                // Asumsi ada relasi students() di model SchoolClass
                'total_students' => $teacher->homeroomClass->students()->count(),
            ];
        }

        // 2. Ambil Jadwal Hari Ini
        $todaySchedules = Schedule::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'subject_name' => $schedule->subject->name ?? '-',
                    'class_name' => $schedule->schoolClass->name ?? '-',
                    'students_count' => $schedule->schoolClass->students()->count() ?? 0,
                ];
            });

        // 3. Ambil Tugas yang Menunggu Penilaian (Ada submission tapi belum di-grade)
        $pendingTasks = Assignment::whereHas('schedule', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })
            ->withCount(['submissions as ungraded_count' => function ($q) {
                // Hitung submission yang TIDAK MEMILIKI relasi grade
                $q->whereDoesntHave('grade');
            }])
            ->with(['schedule.schoolClass', 'schedule.subject'])
            ->having('ungraded_count', '>', 0) // Hanya tampilkan tugas yang ada antrean nilai
            ->orderBy('due_date', 'desc')
            ->take(5)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'subject_name' => $task->schedule->subject->name ?? '-',
                    'class_name' => $task->schedule->schoolClass->name ?? '-',
                    'ungraded_count' => $task->ungraded_count,
                ];
            });

        // 4. Hitung Total Siswa yang Diajar (Unik)
        $totalStudentsTaught = Schedule::where('teacher_id', $teacherId)
            ->with('schoolClass.students')
            ->get()
            ->pluck('schoolClass.students')
            ->flatten()
            ->unique('id')
            ->count();

        // 5. Kembalikan Response
        return response()->json([
            'homeroom_class' => $homeroomClass,
            'stats' => [
                'schedules_today' => $todaySchedules->count(),
                'pending_grading' => $pendingTasks->sum('ungraded_count'),
                'total_students_taught' => $totalStudentsTaught,
            ],
            'today_schedules' => $todaySchedules,
            'pending_tasks' => $pendingTasks,
        ]);
    }
}
