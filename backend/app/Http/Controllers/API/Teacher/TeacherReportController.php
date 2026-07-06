<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\AdminSemesterReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherReportController
{
    public function __construct(
        protected AdminSemesterReportService $reportService,
    ) {}

    /**
     * GET /report/academic-years
     * Return all academic years for the teacher report filter.
     */
    public function academicYears(): JsonResponse
    {
        $years = AcademicYear::orderBy('name', 'desc')->get();

        return response()->json(['success' => true, 'data' => $years]);
    }

    /**
     * GET /report/homeroom-class?academic_year_id=...
     * Return the homeroom class info for the logged-in teacher.
     */
    public function homeroomClass(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $teacherId = auth('api')->user()->id;
        $academicYearId = (int) $request->query('academic_year_id');

        $schoolClass = SchoolClass::where('academic_year_id', $academicYearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->withCount('students')
            ->first();

        if (! $schoolClass) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'total_students' => $schoolClass->students_count,
                'is_published' => $schoolClass->is_published,
            ],
        ]);
    }

    /**
     * GET /report/students?academic_year_id=...
     * Return all students in the homeroom class with grades, attendance, and notes.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $teacherId = auth('api')->user()->id;
        $academicYearId = (int) $request->query('academic_year_id');

        // Find homeroom class for this teacher
        $schoolClass = SchoolClass::where('academic_year_id', $academicYearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->first();

        if (! $schoolClass) {
            return response()->json([
                'success' => true,
                'data' => [
                    'class' => null,
                    'students' => [],
                ],
            ]);
        }

        // 1. Get students with their notes — single query
        $students = DB::select('
            SELECT
                u.id AS user_id,
                u.name,
                s.nis,
                s.nisn,
                cs.note,
                cs.class_id
            FROM class_student cs
            INNER JOIN users u ON u.id = cs.student_id
            INNER JOIN students s ON s.user_id = u.id
            WHERE cs.class_id = ? AND cs.academic_year_id = ?
            ORDER BY u.name
        ', [$schoolClass->id, $academicYearId]);

        // 2. Get attendance summary for all students — single query
        $classScheduleIds = DB::table('schedules')
            ->where('class_id', $schoolClass->id)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id');

        $attendanceSummary = collect();
        if ($classScheduleIds->isNotEmpty()) {
            $attendanceRows = DB::select('
                SELECT
                    a.student_id,
                    a.status,
                    COUNT(*) AS total
                FROM attendances a
                INNER JOIN meeting_sessions ms ON ms.id = a.meeting_session_id
                WHERE ms.schedule_id IN ('.implode(',', array_fill(0, $classScheduleIds->count(), '?')).')
                GROUP BY a.student_id, a.status
            ', $classScheduleIds->toArray());
            $attendanceSummary = collect($attendanceRows)->groupBy('student_id');
        }

        // 3. Get final grades for all students — single query
        $gradeSummary = collect();
        if ($classScheduleIds->isNotEmpty()) {
            $gradeRows = DB::select('
                SELECT
                    sub.student_id,
                    AVG(g.score) AS average_score
                FROM submissions sub
                INNER JOIN grades g ON g.submission_id = sub.id
                INNER JOIN assignments a ON a.id = sub.assignment_id
                WHERE a.schedule_id IN ('.implode(',', array_fill(0, $classScheduleIds->count(), '?')).')
                AND g.score IS NOT NULL
                GROUP BY sub.student_id
            ', $classScheduleIds->toArray());
            $gradeSummary = collect($gradeRows)->keyBy('student_id');
        }

        // 4. Build response
        $result = array_map(function ($student) use ($attendanceSummary, $gradeSummary) {
            $studentAttendance = $attendanceSummary->get($student->user_id, collect());

            $present = $studentAttendance->where('status', 'present')->sum('total');
            $sick = $studentAttendance->where('status', 'sick')->sum('total');
            $permission = $studentAttendance->where('status', 'permission')->sum('total');
            $alpa = $studentAttendance->where('status', 'alpa')->sum('total');
            $totalAttendance = $present + $sick + $permission + $alpa;

            $attendanceRate = $totalAttendance > 0
                ? round(($present / $totalAttendance) * 100, 1)
                : 100;

            $gradeData = $gradeSummary->get($student->user_id);
            $averageScore = $gradeData?->average_score
                ? round((float) $gradeData->average_score, 1)
                : null;

            return [
                'id' => $student->user_id,
                'name' => $student->name,
                'nis' => $student->nis ?? '-',
                'nisn' => $student->nisn ?? '-',
                'note' => $student->note ?? '',
                'attendance' => [
                    'present' => $present,
                    'sick' => $sick,
                    'permission' => $permission,
                    'alpa' => $alpa,
                    'total' => $totalAttendance,
                    'rate' => $attendanceRate,
                ],
                'average_score' => $averageScore,
            ];
        }, $students);

        $totalStudents = count($result);
        $completedNotes = count(array_filter($result, fn ($s) => ! empty(trim($s['note']))));

        return response()->json([
            'success' => true,
            'data' => [
                'class' => [
                    'id' => $schoolClass->id,
                    'name' => $schoolClass->name,
                    'is_published' => $schoolClass->is_published,
                ],
                'students' => $result,
                'summary' => [
                    'total_students' => $totalStudents,
                    'completed_notes' => $completedNotes,
                    'notes_complete' => $totalStudents > 0 && $completedNotes === $totalStudents,
                ],
            ],
        ]);
    }

    /**
     * POST /report/notes
     * Bulk save notes for multiple students.
     */
    public function saveNotes(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'class_id' => 'required|integer|exists:classes,id',
            'notes' => 'required|array',
            'notes.*.student_id' => 'required|integer|exists:users,id',
            'notes.*.note' => 'nullable|string|max:500',
        ]);

        $teacherId = auth('api')->user()->id;
        $academicYearId = (int) $request->input('academic_year_id');
        $classId = (int) $request->input('class_id');
        $notes = $request->input('notes');

        // Verify this teacher is the homeroom teacher
        $schoolClass = SchoolClass::where('id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->first();

        if (! $schoolClass) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan wali kelas untuk kelas ini.',
            ], 403);
        }

        // Bulk update notes
        $updated = 0;
        foreach ($notes as $item) {
            $affected = DB::table('class_student')
                ->where('class_id', $classId)
                ->where('academic_year_id', $academicYearId)
                ->where('student_id', $item['student_id'])
                ->update(['note' => $item['note'] ?? null]);

            $updated += $affected;
        }

        return response()->json([
            'success' => true,
            'message' => "Catatan untuk {$updated} siswa berhasil disimpan.",
        ]);
    }

    /**
     * GET /report/pdf/{studentId}?academic_year_id=...
     * Download PDF rapor for a student (only if class is published).
     */
    public function downloadPdf(Request $request, string $studentId)
    {
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $teacherId = auth('api')->user()->id;
        $academicYearId = (int) $request->query('academic_year_id');
        $studentIdInt = (int) $studentId;

        // Verify the student is in the homeroom teacher's class
        $schoolClass = SchoolClass::where('academic_year_id', $academicYearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->first();

        if (! $schoolClass) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan wali kelas untuk kelas ini.',
            ], 403);
        }

        // Check if student is in this class
        $isStudentInClass = DB::table('class_student')
            ->where('class_id', $schoolClass->id)
            ->where('academic_year_id', $academicYearId)
            ->where('student_id', $studentIdInt)
            ->exists();

        if (! $isStudentInClass) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa tidak ditemukan di kelas Anda.',
            ], 404);
        }

        // Check if class is published
        if (! $schoolClass->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Rapor belum dipublikasikan oleh admin.',
            ], 422);
        }

        try {
            return $this->reportService->downloadStudentPdf($academicYearId, $studentIdInt);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh PDF: '.$e->getMessage(),
            ], 500);
        }
    }
}
