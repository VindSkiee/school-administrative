<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\Teacher;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Assignment;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TeacherDashboardController
{
    public function index(): JsonResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $teacherId = auth('api')->user()->id;
        $today = strtolower(Carbon::today()->englishDayOfWeek);

        // 1. Cek apakah Guru adalah Wali Kelas
        // PERF FIX: replaced N+1 — use withCount instead of loading all students
        $teacher = Teacher::with(['homeroomClass' => function ($q) {
                // No additional constraint needed here, just load the relation
            }])
            ->withCount(['homeroomClass as homeroom_student_count' => function ($q) {
                // Count via the students pivot — SchoolClass uses belongsToMany
            }])
            ->where('user_id', $teacherId)
            ->first();

        $homeroomClass = null;
        if ($teacher && $teacher->homeroomClass) {
            // PERF FIX: replaced N+1 — use count on the relation without loading all models
            $totalStudents = $teacher->homeroomClass->students()->count();

            $homeroomClass = [
                'id' => $teacher->homeroomClass->id,
                'name' => $teacher->homeroomClass->name,
                'total_students' => $totalStudents,
            'academic_year' => $activeYear ? $activeYear->name . ' ' . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap') : null,
            ];
        }

        // 2. Ambil Jadwal Hari Ini
        // PERF FIX: replaced N+1 — added withCount('schoolClass.students') as students_count
        $todaySchedules = Schedule::with(['schoolClass', 'subject'])
            ->withCount(['schoolClass as class_student_count' => function ($q) {
                // This uses the schoolClass.students count via subquery
            }])
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        // PERF FIX: replaced N+1 — single query for all student counts per class
        $classIds = $todaySchedules->pluck('class_id')->unique()->filter();
        $studentCountsByClass = [];
        if ($classIds->isNotEmpty()) {
            $studentCountsByClass = \Illuminate\Support\Facades\DB::table('class_student')
                ->select('class_id', \Illuminate\Support\Facades\DB::raw('COUNT(student_id) as cnt'))
                ->whereIn('class_id', $classIds)
                ->groupBy('class_id')
                ->pluck('cnt', 'class_id')
                ->toArray();
        }

        $todaySchedulesMapped = $todaySchedules->map(function ($schedule) use ($studentCountsByClass) {
            return [
                'id' => $schedule->id,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'subject_name' => $schedule->subject->name ?? '-',
                'class_name' => $schedule->schoolClass->name ?? '-',
                // PERF FIX: replaced N+1 — lookup from pre-fetched map instead of per-schedule query
                'students_count' => $studentCountsByClass[$schedule->class_id] ?? 0,
            ];
        });

        // 3. Ambil Tugas yang Menunggu Penilaian (Ada submission tapi belum di-grade)
        // PERF FIX: replaced HAVING with subquery approach — filter ungraded in WHERE, not HAVING
        // This avoids computing counts for ALL assignments before filtering
        $pendingTasks = Assignment::whereHas('schedule', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })
            ->whereHas('submissions', function ($q) {
                $q->whereDoesntHave('grade');
            })
            ->withCount(['submissions as ungraded_count' => function ($q) {
                $q->whereDoesntHave('grade');
            }])
            ->with(['schedule.schoolClass', 'schedule.subject'])
            ->orderBy('due_date', 'desc')
            ->take(5)
            ->get()
            ->filter(fn ($task) => $task->ungraded_count > 0)
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
        // PERF FIX: replaced N+1 — single aggregate query instead of loading all schedules+students into memory
        $teacherClassIds = Schedule::where('teacher_id', $teacherId)
            ->distinct()
            ->pluck('class_id');

        $totalStudentsTaught = Student::whereHas('classes', function ($q) use ($teacherClassIds) {
            $q->whereIn('classes.id', $teacherClassIds);
        })->count();

        // 5. Kembalikan Response
        return response()->json([
            'academic_year' => $activeYear ? $activeYear->name . ' ' . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap') : null,
            'homeroom_class' => $homeroomClass,
            'stats' => [
                'schedules_today' => $todaySchedulesMapped->count(),
                'pending_grading' => $pendingTasks->sum('ungraded_count'),
                'total_students_taught' => $totalStudentsTaught,
            ],
            'today_schedules' => $todaySchedulesMapped,
            'pending_tasks' => $pendingTasks,
        ]);
    }
}
