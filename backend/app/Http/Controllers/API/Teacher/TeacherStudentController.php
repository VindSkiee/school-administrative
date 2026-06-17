<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherStudentController extends Controller
{
    /**
     * GET /v1/teacher/students/{id}?academic_year_id=...
     * Comprehensive student profile for teacher view.
     * Returns different data scope based on whether viewing teacher is homeroom teacher.
     */
    public function showProfile(Request $request, string $id): JsonResponse
    {
        $teacher = auth('api')->user();
        $teacherId = $teacher->id;
        $studentUserId = (int) $id;

        // 1. Load student user with class history
        $user = User::with(['student.classes.academicYear'])
            ->whereHas('student')
            ->findOrFail($studentUserId);

        $student = $user->student;

        // 2. Determine if this teacher is the student's homeroom teacher
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeClass = $student->classes()
            ->where('classes.academic_year_id', $activeYear?->id)
            ->first();

        $isHomeroom = $activeClass && (int) $activeClass->homeroom_teacher_id === $teacherId;

        // 3. Academic year for data scope
        $selectedYearId = $request->query('academic_year_id')
            ? (int) $request->query('academic_year_id')
            : ($activeYear?->id ?? 0);

        // 4. Get class for the selected year
        $selectedClass = $student->classes()
            ->where('classes.academic_year_id', $selectedYearId)
            ->first();

        $selectedClassId = $selectedClass?->id;

        // 5. All academic years (for filter dropdown)
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        // 6. Schedules for the student's class in the selected year
        $schedules = collect();
        $subjectsGrades = collect();

        if ($selectedClassId) {
            $schedules = Schedule::with(['subject', 'teacher.user'])
                ->where('class_id', $selectedClassId)
                ->where('academic_year_id', $selectedYearId)
                ->orderByRaw("CASE day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 WHEN 'saturday' THEN 6 ELSE 7 END")
                ->orderBy('start_time')
                ->get();

            // 7. Build grades per subject
            $subjectsGrades = $schedules->map(function (Schedule $schedule) use ($studentUserId, $teacherId, $isHomeroom) {
                $isMySubject = (int) $schedule->teacher_id === $teacherId;

                // Attendance for this schedule + student
                $attendanceQuery = Attendance::where('schedule_id', $schedule->id)
                    ->where('student_id', $studentUserId);
                $totalMeetings = $attendanceQuery->count();
                $presentCount = (clone $attendanceQuery)->where('status', 'present')->count();
                $attendanceRate = $totalMeetings > 0 ? round(($presentCount / $totalMeetings) * 100, 1) : null;

                // Get all assignments for this schedule
                $assignments = Assignment::where('schedule_id', $schedule->id)->get();
                $assignmentIds = $assignments->pluck('id');

                // Get submissions + grades for this student
                $submissions = Submission::with('grade')
                    ->where('student_id', $studentUserId)
                    ->whereIn('assignment_id', $assignmentIds)
                    ->get()
                    ->keyBy('assignment_id');

                $totalScore = 0;
                $gradedCount = 0;

                $assignmentDetails = $assignments->map(function ($assignment) use ($submissions, &$totalScore, &$gradedCount) {
                    $submission = $submissions->get($assignment->id);
                    $grade = $submission?->grade;
                    $score = $grade?->score;

                    if ($score !== null) {
                        $totalScore += $score;
                        $gradedCount++;
                    }

                    return [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'type' => $assignment->type,
                        'due_date' => $assignment->due_date,
                        'score' => $score,
                    ];
                });

                $averageScore = $gradedCount > 0 ? round($totalScore / $gradedCount, 2) : null;

                // Only include full assignment details if this is the viewing teacher's subject
                // or if the viewer is the homeroom teacher (sees everything)
                $includeDetails = $isMySubject || $isHomeroom;

                return [
                    'schedule_id' => $schedule->id,
                    'subject_id' => $schedule->subject_id,
                    'subject_name' => $schedule->subject?->name,
                    'teacher_name' => $schedule->teacher?->user?->name,
                    'teacher_id' => $schedule->teacher_id,
                    'is_my_subject' => $isMySubject,
                    'assignments' => $includeDetails ? $assignmentDetails : [],
                    'total_assignments' => $assignments->count(),
                    'graded_assignments' => $gradedCount,
                    'average_score' => $averageScore,
                    'attendance' => [
                        'total' => $totalMeetings,
                        'present' => $presentCount,
                        'rate' => $attendanceRate,
                    ],
                ];
            });

            // For non-homeroom teachers, only show subjects they teach
            if (! $isHomeroom) {
                $subjectsGrades = $subjectsGrades->filter(fn ($item) => $item['is_my_subject'])->values();
            }
        }

        // 8. Class history with semester translation
        $classHistory = $student->classes->map(function ($class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'academic_year' => [
                    'id' => $class->academicYear?->id,
                    'name' => $class->academicYear?->name,
                    'semester' => $class->academicYear?->semester,
                    'semester_label' => $class->academicYear?->semester === 'odd'
                        ? 'Ganjil'
                        : ($class->academicYear?->semester === 'even' ? 'Genap' : '-'),
                    'is_active' => $class->academicYear?->is_active ?? false,
                ],
            ];
        })->sortByDesc(fn ($c) => $c['academic_year']['id'] ?? 0)->values();

        // 9. Overall attendance summary (across all subjects for selected year)
        $allScheduleIds = $schedules->pluck('id');
        $overallAttendance = [];
        if ($allScheduleIds->isNotEmpty()) {
            $overallTotal = Attendance::whereIn('schedule_id', $allScheduleIds)
                ->where('student_id', $studentUserId)
                ->count();
            $overallPresent = Attendance::whereIn('schedule_id', $allScheduleIds)
                ->where('student_id', $studentUserId)
                ->where('status', 'present')
                ->count();
            $overallAttendance = [
                'total' => $overallTotal,
                'present' => $overallPresent,
                'rate' => $overallTotal > 0 ? round(($overallPresent / $overallTotal) * 100, 1) : null,
            ];
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'is_active' => $user->is_active,
            'gender' => $student->gender,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'is_homeroom' => $isHomeroom,
            'class_name' => $selectedClass?->name,
            'class_history' => $classHistory,
            'academic_years' => $academicYears->map(fn ($y) => [
                'id' => $y->id,
                'name' => $y->name,
                'semester' => $y->semester,
                'semester_label' => $y->semester === 'odd' ? 'Ganjil' : ($y->semester === 'even' ? 'Genap' : '-'),
                'is_active' => $y->is_active,
            ]),
            'selected_academic_year_id' => $selectedYearId,
            'schedules' => $schedules->map(fn ($s) => [
                'id' => $s->id,
                'day_of_week' => $s->day_of_week,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'subject_name' => $s->subject?->name,
                'teacher_name' => $s->teacher?->user?->name,
                'teacher_id' => $s->teacher_id,
                'is_my_subject' => (int) $s->teacher_id === $teacherId,
            ]),
            'subjects_grades' => $subjectsGrades->values(),
            'overall_attendance' => $overallAttendance,
        ]);
    }
}
