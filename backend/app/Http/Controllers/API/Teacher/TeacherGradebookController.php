<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradingSetting;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Submission;
use App\Models\Subject;
use App\Services\GradeAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherGradebookController
{
    public function __construct(protected GradeAggregationService $gradeAggregation) {}

    /**
     * GET /gradebook/academic-years
     * Return all academic years for the gradebook filter dropdown.
     */
    public function academicYears(): JsonResponse
    {
        $years = AcademicYear::orderBy('name', 'desc')->get();

        return response()->json(['success' => true, 'data' => $years]);
    }

    /**
     * GET /gradebook/schedules?academic_year_id=...
     * Return all schedules taught by the logged-in teacher for a given academic year.
     */
    public function schedules(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $teacherId = auth('api')->user()->id;
        $academicYearId = (int) $request->query('academic_year_id');

        $schedules = Schedule::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) use ($teacherId) {
                $schedule->is_homeroom = $schedule->schoolClass
                    && $schedule->schoolClass->homeroom_teacher_id === $teacherId;
                return $schedule;
            });

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    /**
     * GET /gradebook?schedule_id=...&academic_year_id=...
     * Return gradebook data: weights, assignments, and students with scores.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'schedule_id' => 'required|integer|exists:schedules,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $teacherId = auth('api')->user()->id;
        $scheduleId = (int) $request->query('schedule_id');
        $academicYearId = (int) $request->query('academic_year_id');

        $schedule = Schedule::with('schoolClass')->findOrFail($scheduleId);

        if ($schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        // 1. Grading weights
        $settings = GradingSetting::where('academic_year_id', $academicYearId)->first();
        $weights = [
            'task' => $settings ? $settings->task_weight : 40,
            'uts' => $settings ? $settings->uts_weight : 25,
            'uas' => $settings ? $settings->uas_weight : 25,
            'attendance' => $settings ? $settings->attendance_weight : 10,
        ];

        // 2. Assignments for this schedule, ordered: task → uts → uas
        $assignments = Assignment::where('schedule_id', $scheduleId)
            ->orderByRaw("FIELD(type, 'task', 'uts', 'uas')")
            ->orderBy('id')
            ->get(['id', 'title', 'type', 'schedule_id']);

        // 3. Students in this class with their submissions
        $students = Student::with(['user:id,name', 'submissions.grade'])
            ->whereHas('classes', function ($query) use ($schedule) {
                $query->where('classes.id', $schedule->class_id);
            })
            ->where('status', 'active')
            ->orderBy('nisn')
            ->get();

        // 4. Calculate attendance rate per student across all schedules in this class
        $allScheduleIds = Schedule::where('class_id', $schedule->class_id)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id');

        $studentData = $students->map(function ($student) use ($assignments, $allScheduleIds) {
            // Attendance rate
            $attendances = Attendance::whereIn('schedule_id', $allScheduleIds)
                ->where('student_id', $student->user_id)
                ->get();

            $totalAttendances = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $attendanceRate = $totalAttendances > 0
                ? round(($present / $totalAttendances) * 100, 2)
                : 100;

            // Map assignment_id → score from submissions
            $assignmentScores = [];
            foreach ($assignments as $assignment) {
                $submission = $student->submissions
                    ->firstWhere('assignment_id', $assignment->id);

                $assignmentScores[$assignment->id] = $submission?->grade?->score;
            }

            return [
                'id' => $student->user_id,
                'name' => $student->user?->name ?? 'Tanpa Nama',
                'nis' => $student->nis ?? '-',
                'attendance_rate' => $attendanceRate,
                'assignments' => $assignmentScores,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'weights' => $weights,
                'assignments' => $assignments,
                'students' => $studentData->values(),
            ],
        ]);
    }

    /**
     * GET /homeroom/gradebook-recap?class_id=...&academic_year_id=...
     * Return cross-subject recap for the homeroom teacher's class.
     */
    public function homeroomRecap(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $teacherId = auth('api')->user()->id;
        $classId = (int) $request->query('class_id');
        $academicYearId = (int) $request->query('academic_year_id');

        // Verify teacher is the homeroom teacher
        $schedule = Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (! $schedule) {
            return response()->json(['error' => 'Kelas tidak ditemukan.'], 404);
        }

        $schoolClass = $schedule->schoolClass;
        if (! $schoolClass || $schoolClass->homeroom_teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak: Anda bukan wali kelas ini.'], 403);
        }

        // Get all unique subjects taught in this class for this academic year
        $subjects = Subject::whereHas('schedules', function ($q) use ($classId, $academicYearId) {
            $q->where('class_id', $classId)
              ->where('academic_year_id', $academicYearId);
        })->get(['id', 'name']);

        // Get all active students in this class
        $students = Student::with(['user:id,name', 'submissions.grade'])
            ->whereHas('classes', function ($query) use ($classId) {
                $query->where('classes.id', $classId);
            })
            ->where('status', 'active')
            ->orderBy('nisn')
            ->get();

        // All schedule IDs in this class for attendance calculation
        $allScheduleIds = Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id');

        $studentData = $students->map(function ($student) use ($classId, $academicYearId, $allScheduleIds) {
            // Attendance rate
            $attendances = Attendance::whereIn('schedule_id', $allScheduleIds)
                ->where('student_id', $student->user_id)
                ->get();

            $totalAttendances = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $attendanceRate = $totalAttendances > 0
                ? round(($present / $totalAttendances) * 100, 2)
                : 100;

            // Calculate weighted average per subject using the existing service
            $subjectGrades = [];
            $subjectSchedules = Schedule::where('class_id', $classId)
                ->where('academic_year_id', $academicYearId)
                ->with('subject')
                ->get()
                ->groupBy('subject_id');

            foreach ($subjectSchedules as $subjectId => $schedules) {
                // Get all assignment IDs for this subject in this class
                $assignmentIds = Assignment::whereIn('schedule_id', $schedules->pluck('id'))->pluck('id');

                // Get graded submissions for this student
                $gradedScores = \Illuminate\Support\Facades\DB::table('grades')
                    ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
                    ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                    ->where('submissions.student_id', $student->user_id)
                    ->whereIn('submissions.assignment_id', $assignmentIds)
                    ->avg('grades.score');

                $subjectGrades[$subjectId] = $gradedScores !== null ? round((float) $gradedScores, 2) : null;
            }

            return [
                'id' => $student->user_id,
                'name' => $student->user?->name ?? 'Tanpa Nama',
                'nis' => $student->nis ?? '-',
                'attendance_rate' => $attendanceRate,
                'subjects' => $subjectGrades,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'subjects' => $subjects,
                'students' => $studentData->values(),
            ],
        ]);
    }

    /**
     * POST /gradebook/inline-save
     * UPSERT: Save a grade directly from the gradebook grid using student_id + assignment_id.
     * Creates a submission if the student hasn't submitted anything yet.
     */
    public function inlineSave(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,user_id',
            'assignment_id' => 'required|integer|exists:assignments,id',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);

        $teacherId = auth('api')->user()->id;
        $studentId = (int) $request->input('student_id');
        $assignmentId = (int) $request->input('assignment_id');
        $score = $request->input('score');

        // Verify teacher owns the assignment's schedule
        $assignment = Assignment::with('schedule')->findOrFail($assignmentId);
        if ($assignment->schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        // Find or create the submission for this student+assignment pair
        $submission = Submission::firstOrCreate(
            [
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
            ],
            [
                'file_path' => null,
                'submitted_at' => now(),
            ]
        );

        // Update or create the grade
        Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'score' => $score,
                'feedback' => $submission->grade?->feedback,
                'graded_by' => $teacherId,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Nilai disimpan.',
        ]);
    }
}
