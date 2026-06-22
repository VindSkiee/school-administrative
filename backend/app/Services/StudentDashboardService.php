<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\Student;
use App\Models\SchoolClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentDashboardService
{
    public function getDashboardData(Student $student, int $activeClassId): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $dayOfWeek = strtolower(Carbon::today()->englishDayOfWeek);

        $activeYear = AcademicYear::where('is_active', true)->first();

        // 1. Data Kelas (Homeroom) - TAMBAHKAN RELASI 'homeroomTeacher.user'
        $schoolClass = SchoolClass::with('homeroomTeacher.user')->find($activeClassId);
        $classInfo = null;
        if ($schoolClass) {
            $classInfo = [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                // Ambil Nama Wali Kelas dari tabel users
                'homeroom_teacher_name' => $schoolClass->homeroomTeacher->user->name ?? 'Belum Ditentukan',
                'total_students' => DB::table('class_student')->where('class_id', $activeClassId)->count(),
            ];
        }

        // 2. Jadwal Hari Ini
        $todaySchedules = Schedule::with(['subject', 'teacher.user'])
            ->where('class_id', $activeClassId)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'subject_name' => $schedule->subject->name ?? 'Mata Pelajaran',
                    'teacher_name' => $schedule->teacher->user->name ?? 'Guru Pengajar', // Sudah ada di sini
                ];
            });

        // 3. Tugas dengan Tenggat Waktu Terdekat (Belum dikerjakan)
        $deadlineAssignments = Assignment::with(['schedule.subject'])
            ->whereHas('schedule', function ($query) use ($activeClassId) {
                $query->where('class_id', $activeClassId);
            })
            ->where('due_date', '>', now()) 
            ->whereDoesntHave('submissions', function ($query) use ($student) {
                $query->where('student_id', $student->user_id); 
            })
            ->orderBy('due_date', 'asc')
            ->take(4) 
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'due_date' => $assignment->due_date,
                    'subject_name' => $assignment->schedule->subject->name ?? 'Unknown',
                ];
            });

        // 4. Nilai Terbaru (Baru saja dinilai guru)
        $recentGrades = Submission::with(['assignment.schedule.subject', 'grade'])
            ->where('student_id', $student->user_id)
            ->whereHas('grade') 
            ->orderByDesc(function($query) {
                $query->select('created_at')
                    ->from('grades')
                    ->whereColumn('submission_id', 'submissions.id')
                    ->limit(1);
            })
            ->take(3) 
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'assignment_id' => $submission->assignment_id,
                    'assignment_title' => $submission->assignment->title ?? 'Unknown',
                    'subject_name' => $submission->assignment->schedule->subject->name ?? 'Unknown',
                    'grade' => [
                        'score' => $submission->grade->score ?? 0,
                    ]
                ];
            });

        return [
            'academic_year' => $activeYear ? $activeYear->name . ' ' . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap') : null,
            'homeroom_class' => $classInfo,
            'today_schedules' => $todaySchedules,
            'deadline_assignments' => $deadlineAssignments,
            'recent_grades' => $recentGrades,
        ];
    }
}