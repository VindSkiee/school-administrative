<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\AcademicYear;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

class TeacherHomeroomController
{
    public function show(): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        // PERF FIX: replaced unbounded eager loads with academic year-scoped queries
        $activeYear = AcademicYear::where('is_active', true)->first();

        $teacher = Teacher::with([
            'homeroomClass' => function ($query) use ($activeYear) {
                if ($activeYear) {
                    // PERF FIX: scope to active academic year only
                    $query->where('academic_year_id', $activeYear->id);
                }
            },
            'homeroomClass.students' => function ($query) {
                $query->where('status', 'active');
            },
            'homeroomClass.students.user:id,name',
        ])->where('user_id', $teacherId)->first();

        if (!$teacher || !$teacher->homeroomClass) {
            return response()->json(['error' => 'Anda belum ditetapkan sebagai Wali Kelas.'], 403);
        }

        $class = $teacher->homeroomClass;
        $studentIds = $class->students->pluck('user_id');

        // PERF FIX: replaced N+1 — single attendance query for all students, scoped to this class's schedules
        $classScheduleIds = $class->schedules()->pluck('id');

        $attendancesByStudent = \App\Models\Attendance::whereIn('schedule_id', $classScheduleIds)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        // PERF FIX: replaced N+1 — single submission+grade query for all students
        $submissionIds = \App\Models\Submission::whereIn('student_id', $studentIds)
            ->pluck('id');

        $gradesByStudent = \App\Models\Grade::whereIn('submission_id', $submissionIds)
            ->whereNotNull('score')
            ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
            ->select('submissions.student_id', 'grades.score')
            ->get()
            ->groupBy('student_id');

        // PERF FIX: replaced N+1 — zero queries inside map()
        $studentsData = $class->students->map(function ($student) use ($attendancesByStudent, $gradesByStudent) {
            $attendances = $attendancesByStudent->get($student->user_id, collect());
            $totalAttendances = $attendances->count();

            $present = $attendances->where('status', 'present')->count();
            $sick = $attendances->where('status', 'sick')->count();
            $permission = $attendances->where('status', 'permission')->count();
            $alpa = $attendances->where('status', 'alpa')->count();

            $attendanceRate = $totalAttendances > 0
                ? round(($present / $totalAttendances) * 100, 2)
                : 100;

            // Average score from pre-fetched grades lookup
            $studentGrades = $gradesByStudent->get($student->user_id, collect());
            $averageScore = $studentGrades->count() > 0
                ? round($studentGrades->avg('score'), 2)
                : 0;

            return [
                'id' => $student->user_id ?? $student->user->id,
                'user_id' => $student->user_id ?? $student->user->id,
                'nis' => $student->nis ?? '-',
                'name' => $student->user->name ?? 'Tanpa Nama',
                'gender' => $student->gender ?? 'L',
                'status' => $student->status,
                'present' => $present,
                'sick' => $sick,
                'permission' => $permission,
                'alpa' => $alpa,
                'attendance_rate' => $attendanceRate,
                'math_score' => $averageScore,
                'bio_score' => $averageScore,
                'average_score' => $averageScore,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'class_info' => [
                'id' => $class->id,
                'name' => $class->name,
                'total_students' => $studentsData->count(),
            ],
            'students' => $studentsData
        ]);
    }
}
