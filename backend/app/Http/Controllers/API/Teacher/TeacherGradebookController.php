<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradingSetting;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Services\GradeAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherGradebookController
{
    public function __construct(protected GradeAggregationService $gradeAggregation) {}

    /**
     * GET /report-status
     * Return per-class publish status for the logged-in teacher.
     */
    public function reportStatus(): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (! $activeYear) {
            return response()->json([
                'is_report_published' => false,
                'published_at' => null,
                'classes' => [],
            ]);
        }

        $teacherClasses = SchoolClass::where('academic_year_id', $activeYear->id)
            ->whereHas('schedules', fn ($q) => $q->where('teacher_id', $teacherId))
            ->get(['id', 'name', 'is_published', 'published_at']);

        $anyPublished = $teacherClasses->contains('is_published', true);

        return response()->json([
            'is_report_published' => $anyPublished,
            'published_at' => $anyPublished ? $teacherClasses->where('is_published', true)->first()->published_at : null,
            'classes' => $teacherClasses->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'is_published' => $c->is_published,
                'published_at' => $c->published_at,
            ]),
        ]);
    }

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

        // 3. Students in this class with their submissions (eager loaded)
        $students = Student::with(['user:id,name', 'submissions.grade'])
            ->whereHas('classes', function ($query) use ($schedule) {
                $query->where('classes.id', $schedule->class_id);
            })
            ->where('status', 'active')
            ->orderBy('nisn')
            ->get();

        // PERF FIX: replaced N+1 with pre-fetched lookup — collect all student IDs upfront
        $studentIds = $students->pluck('user_id');

        // 4. All schedule IDs in this class for attendance calculation
        $allScheduleIds = Schedule::where('class_id', $schedule->class_id)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id');

        // PERF FIX: replaced N+1 with pre-fetched lookup — single attendance query for all students
        $attendancesByStudent = Attendance::whereIn('schedule_id', $allScheduleIds)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        // PERF FIX: replaced N+1 with pre-fetched lookup — zero queries inside map()
        $studentData = $students->map(function ($student) use ($assignments, $attendancesByStudent) {
            // Attendance rate from pre-fetched lookup
            $attendances = $attendancesByStudent->get($student->user_id, collect());

            $totalAttendances = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $attendanceRate = $totalAttendances > 0
                ? round(($present / $totalAttendances) * 100, 2)
                : 100;

            // Map assignment_id → score from eager-loaded submissions
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
        $students = Student::with(['user:id,name'])
            ->whereHas('classes', function ($query) use ($classId) {
                $query->where('classes.id', $classId);
            })
            ->where('status', 'active')
            ->orderBy('nisn')
            ->get();

        // PERF FIX: replaced N+1 with pre-fetched lookup — collect student IDs upfront
        $studentIds = $students->pluck('user_id');

        // PERF FIX: replaced N+1 with pre-fetched lookup — single query for all schedule IDs
        $allScheduleIds = Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id');

        // PERF FIX: replaced N+1 with pre-fetched lookup — single attendance query for all students
        $attendancesByStudent = Attendance::whereIn('schedule_id', $allScheduleIds)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        // PERF FIX: replaced N+1 with pre-fetched lookup — single query: all schedules grouped by subject
        $schedulesBySubject = Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id', 'subject_id'); // subject_id => [schedule_ids]

        // PERF FIX: replaced N+1 with pre-fetched lookup — group schedule IDs by subject
        $scheduleIdsGroupedBySubject = Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->get(['id', 'subject_id'])
            ->groupBy('subject_id')
            ->map(fn ($group) => $group->pluck('id'));

        // PERF FIX: replaced N+1 with pre-fetched lookup — single query for all assignment IDs
        $allAssignmentIds = Assignment::whereIn('schedule_id', $allScheduleIds)->pluck('id');

        // PERF FIX: replaced N+1 with pre-fetched lookup — single query: all graded submissions for these students+assignments
        $gradedScoresLookup = DB::table('grades')
            ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
            ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
            ->whereIn('submissions.student_id', $studentIds)
            ->whereIn('submissions.assignment_id', $allAssignmentIds)
            ->whereNotNull('grades.score')
            ->select('submissions.student_id', 'assignments.schedule_id', 'grades.score')
            ->get();

        // Build lookup: student_id => schedule_id => [scores]
        $scoresByStudentSchedule = [];
        foreach ($gradedScoresLookup as $row) {
            $scoresByStudentSchedule[$row->student_id][$row->schedule_id][] = (float) $row->score;
        }

        // PERF FIX: replaced N+1 with pre-fetched lookup — zero queries inside map()
        $studentData = $students->map(function ($student) use (
            $attendancesByStudent, $scheduleIdsGroupedBySubject, $scoresByStudentSchedule
        ) {
            // Attendance rate from pre-fetched lookup
            $attendances = $attendancesByStudent->get($student->user_id, collect());
            $totalAttendances = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $attendanceRate = $totalAttendances > 0
                ? round(($present / $totalAttendances) * 100, 2)
                : 100;

            // Calculate average per subject from pre-fetched lookup
            $subjectGrades = [];
            foreach ($scheduleIdsGroupedBySubject as $subjectId => $schedIds) {
                $allScores = [];
                foreach ($schedIds as $schedId) {
                    $scores = $scoresByStudentSchedule[$student->user_id][$schedId] ?? [];
                    $allScores = array_merge($allScores, $scores);
                }

                $subjectGrades[$subjectId] = ! empty($allScores)
                    ? round(array_sum($allScores) / count($allScores), 2)
                    : null;
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
        $assignment = Assignment::with('schedule.schoolClass')->findOrFail($assignmentId);
        if ($assignment->schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        // Block grade changes when the class is published
        $schoolClass = $assignment->schedule->schoolClass;
        if ($schoolClass && $schoolClass->is_published) {
            return response()->json([
                'error' => 'Kelas ini sudah dipublikasikan. Anda tidak dapat lagi mengubah nilai.',
            ], 403);
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
