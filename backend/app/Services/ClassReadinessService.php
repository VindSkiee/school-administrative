<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\SubjectCompetencySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClassReadinessService
{
    /**
     * Ambil status kesiapan satu kelas untuk dipublikasikan.
     *
     * @return array{class_id:int,class_name:string,homeroom_teacher:string,is_published:bool,is_ready:bool,readiness:array}
     */
    public function getClassReadiness(int $classId, int $academicYearId): array
    {
        $schoolClass = SchoolClass::query()
            ->with('homeroomTeacher.user')
            ->findOrFail($classId);

        $attendanceDetails = $this->getAttendanceReadiness($classId, $academicYearId);
        $gradeDetails = $this->getGradeReadiness($classId, $academicYearId);
        $studentDetails = $this->getStudentReadiness($classId, $academicYearId);
        $competencyDetails = $this->getCompetencyReadiness($classId, $academicYearId);

        $allAttendanceComplete = collect($attendanceDetails)->every(fn (array $d): bool => $d['is_complete']);
        $allGradesComplete = collect($gradeDetails)->every(fn (array $d): bool => $d['is_complete']);
        $allStudentsReady = $studentDetails['not_ready_count'] === 0;
        $allCompetencyConfigured = collect($competencyDetails)->every(fn (array $d): bool => $d['is_configured']);

        return [
            'class_id' => $schoolClass->id,
            'class_name' => $schoolClass->name,
            'homeroom_teacher' => $schoolClass->homeroomTeacher?->user?->name ?? '-',
            'is_published' => $schoolClass->is_published,
            'published_at' => $schoolClass->published_at?->toIso8601String(),
            'is_ready' => $allAttendanceComplete && $allGradesComplete && $allStudentsReady && $allCompetencyConfigured,
            'readiness' => [
                'attendance' => [
                    'is_complete' => $allAttendanceComplete,
                    'details' => $attendanceDetails,
                ],
                'grades' => [
                    'is_complete' => $allGradesComplete,
                    'details' => $gradeDetails,
                ],
                'competency' => [
                    'is_complete' => $allCompetencyConfigured,
                    'details' => $competencyDetails,
                ],
                'students' => [
                    'total' => $studentDetails['total_count'],
                    'ready' => $studentDetails['ready_count'],
                    'not_ready' => $studentDetails['not_ready'],
                ],
            ],
        ];
    }

    /**
     * Ambil kesiapan semua kelas dalam satu tahun ajaran.
     */
    public function getClassesReadiness(int $academicYearId): array
    {
        $classes = SchoolClass::query()
            ->where('academic_year_id', $academicYearId)
            ->with('homeroomTeacher.user')
            ->get();

        $results = [];
        foreach ($classes as $schoolClass) {
            $results[] = $this->getClassReadiness($schoolClass->id, $academicYearId);
        }

        $totalClasses = count($results);
        $readyClasses = collect($results)->where('is_ready', true)->count();
        $publishedClasses = collect($results)->where('is_published', true)->count();

        $academicYear = AcademicYear::query()->find($academicYearId);

        return [
            'summary' => [
                'total_classes' => $totalClasses,
                'ready_classes' => $readyClasses,
                'published_classes' => $publishedClasses,
                'can_publish_all' => $readyClasses === $totalClasses && $totalClasses > 0,
                'is_report_published' => $academicYear?->is_report_published ?? false,
            ],
            'classes' => $results,
        ];
    }

    /**
     * Ambil detail kesiapan satu kelas — per mapel, per tipe penilaian.
     */
    public function getClassReadinessDetail(int $classId, int $academicYearId): array
    {
        $schoolClass = SchoolClass::query()
            ->with('homeroomTeacher.user')
            ->findOrFail($classId);

        // 1. Attendance per schedule (subject) — only count sessions up to today
        $today = now()->toDateString();

        $attendanceRows = DB::select("
            SELECT
                s.id AS schedule_id,
                sub.name AS subject_name,
                u.name AS teacher_name,
                (SELECT COUNT(*) FROM meeting_sessions ms WHERE ms.schedule_id = s.id AND ms.date <= ? AND ms.status != 'holiday') AS total_meetings,
                (SELECT COUNT(DISTINCT ms2.id)
                 FROM meeting_sessions ms2
                 INNER JOIN attendances a ON a.meeting_session_id = ms2.id
                 WHERE ms2.schedule_id = s.id AND ms2.date <= ?
                ) AS recorded_meetings
            FROM schedules s
            INNER JOIN subjects sub ON sub.id = s.subject_id
            INNER JOIN teachers t ON t.user_id = s.teacher_id
            INNER JOIN users u ON u.id = t.user_id
            WHERE s.class_id = ? AND s.academic_year_id = ?
            ORDER BY sub.name
        ", [$today, $today, $classId, $academicYearId]);

        // 2. Grades per schedule — grouped by type (task, uts, uas)
        $gradeRows = DB::select('
            SELECT
                s.id AS schedule_id,
                sub.name AS subject_name,
                a.type AS assignment_type,
                COUNT(DISTINCT a.id) AS total_assignments,
                COUNT(DISTINCT CASE WHEN g.id IS NOT NULL THEN sub2.id END) AS graded_submissions,
                AVG(CASE WHEN g.id IS NOT NULL THEN g.score END) AS average_score
            FROM schedules s
            INNER JOIN subjects sub ON sub.id = s.subject_id
            INNER JOIN assignments a ON a.schedule_id = s.id
            LEFT JOIN submissions sub2 ON sub2.assignment_id = a.id
            LEFT JOIN grades g ON g.submission_id = sub2.id
            WHERE s.class_id = ? AND s.academic_year_id = ?
            GROUP BY s.id, sub.name, a.type
            ORDER BY sub.name, a.type
        ', [$classId, $academicYearId]);

        // 3. Competency settings — check which subjects have configured competency
        $configuredCompetencySubjectIds = SubjectCompetencySetting::where('academic_year_id', $academicYearId)
            ->pluck('subject_id')
            ->toArray();

        // 4. Student readiness
        $studentDetails = $this->getStudentReadiness($classId, $academicYearId);

        // --- Build per-subject detail ---
        $subjectMap = [];

        foreach ($attendanceRows as $row) {
            $sid = $row->schedule_id;
            $total = (int) $row->total_meetings;
            $recorded = (int) $row->recorded_meetings;
            $pct = $total > 0 ? round(($recorded / $total) * 100, 1) : 0;

            $subjectMap[$sid] = [
                'schedule_id' => $sid,
                'subject' => $row->subject_name,
                'teacher' => $row->teacher_name,
                'attendance' => [
                    'total_meetings' => $total,
                    'recorded' => $recorded,
                    'missing' => max(0, $total - $recorded),
                    'percentage' => $pct,
                    'is_complete' => $total > 0 && $recorded >= $total,
                ],
                'grades' => [
                    'task' => ['exists' => false, 'graded' => 0, 'total' => 0, 'average' => null, 'is_complete' => false],
                    'uts' => ['exists' => false, 'graded' => 0, 'total' => 0, 'average' => null, 'is_complete' => false],
                    'uas' => ['exists' => false, 'graded' => 0, 'total' => 0, 'average' => null, 'is_complete' => false],
                ],
                'competency_configured' => false,
                'is_subject_ready' => false,
            ];
        }

        foreach ($gradeRows as $row) {
            $sid = $row->schedule_id;
            if (! isset($subjectMap[$sid])) {
                continue;
            }

            $type = $row->assignment_type;
            $totalAssignments = (int) $row->total_assignments;
            $gradedSubs = (int) $row->graded_submissions;
            $avgScore = $row->average_score !== null ? round((float) $row->average_score, 1) : null;

            $subjectMap[$sid]['grades'][$type] = [
                'exists' => $totalAssignments > 0,
                'graded' => $gradedSubs,
                'total' => $totalAssignments,
                'average' => $avgScore,
                'is_complete' => $gradedSubs >= 1,
            ];
        }

        // Determine per-subject readiness (including competency)
        foreach ($subjectMap as &$subject) {
            $attComplete = $subject['attendance']['is_complete'];
            $taskOk = $subject['grades']['task']['is_complete'];
            $utsOk = $subject['grades']['uts']['is_complete'];
            $uasOk = $subject['grades']['uas']['is_complete'];

            // Resolve subject_id from schedule_id for competency check
            $subjectId = DB::table('schedules')->where('id', $subject['schedule_id'])->value('subject_id');
            $subject['competency_configured'] = in_array($subjectId, $configuredCompetencySubjectIds, true);

            $subject['is_subject_ready'] = $attComplete && $taskOk && $utsOk && $uasOk && $subject['competency_configured'];
        }
        unset($subject);

        $subjects = array_values($subjectMap);

        // Summary
        $totalSubjects = count($subjects);
        $readySubjects = collect($subjects)->where('is_subject_ready', true)->count();

        return [
            'class_id' => $schoolClass->id,
            'class_name' => $schoolClass->name,
            'homeroom_teacher' => $schoolClass->homeroomTeacher?->user?->name ?? '-',
            'subjects' => $subjects,
            'summary' => [
                'total_subjects' => $totalSubjects,
                'ready_subjects' => $readySubjects,
                'students_ready' => $studentDetails['ready_count'],
                'students_total' => $studentDetails['total_count'],
            ],
        ];
    }

    /**
     * Publish satu kelas. Auto-finalize academic year jika semua kelas sudah published.
     */
    public function publishClass(int $classId, int $academicYearId): array
    {
        $readiness = $this->getClassReadiness($classId, $academicYearId);

        if ($readiness['is_published']) {
            return ['success' => false, 'message' => 'Kelas ini sudah dipublikasikan sebelumnya.'];
        }

        if (! $readiness['is_ready']) {
            return ['success' => false, 'message' => 'Kelas belum siap dipublikasikan. Lengkapi data kehadiran dan nilai terlebih dahulu.'];
        }

        $schoolClass = SchoolClass::findOrFail($classId);
        $schoolClass->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Check if all classes in this academic year are now published
        $this->checkAndFinalizeAcademicYear($academicYearId);

        return ['success' => true, 'message' => "Kelas {$readiness['class_name']} berhasil dipublikasikan."];
    }

    /**
     * Publish semua kelas yang siap dalam satu tahun ajaran.
     */
    public function publishAllReadyClasses(int $academicYearId): array
    {
        $readinessData = $this->getClassesReadiness($academicYearId);
        $results = ['published' => [], 'skipped' => []];

        foreach ($readinessData['classes'] as $classReadiness) {
            if ($classReadiness['is_published']) {
                $results['skipped'][] = [
                    'class_id' => $classReadiness['class_id'],
                    'class_name' => $classReadiness['class_name'],
                    'reason' => 'Sudah dipublikasikan',
                ];

                continue;
            }

            if (! $classReadiness['is_ready']) {
                $results['skipped'][] = [
                    'class_id' => $classReadiness['class_id'],
                    'class_name' => $classReadiness['class_name'],
                    'reason' => 'Data belum lengkap',
                ];

                continue;
            }

            $publishResult = $this->publishClass($classReadiness['class_id'], $academicYearId);
            $results['published'][] = [
                'class_id' => $classReadiness['class_id'],
                'class_name' => $classReadiness['class_name'],
                'success' => $publishResult['success'],
            ];
        }

        return $results;
    }

    /**
     * Check if all classes in an academic year are published.
     * If so, set end_date to the last published_at timestamp.
     */
    private function checkAndFinalizeAcademicYear(int $academicYearId): void
    {
        $academicYear = AcademicYear::query()->find($academicYearId);
        if (! $academicYear) {
            return;
        }

        $totalClasses = SchoolClass::where('academic_year_id', $academicYearId)->count();
        $publishedClasses = SchoolClass::where('academic_year_id', $academicYearId)
            ->where('is_published', true)
            ->count();

        if ($totalClasses > 0 && $publishedClasses === $totalClasses) {
            $lastPublished = SchoolClass::where('academic_year_id', $academicYearId)
                ->whereNotNull('published_at')
                ->max('published_at');

            $updateData = ['end_date' => $lastPublished];

            // Auto-publish rapor jika semua kelas sudah published
            if (! $academicYear->is_report_published) {
                $updateData['is_report_published'] = true;
            }

            $academicYear->update($updateData);

            // Invalidate academic years cache so AcademicYearManagement shows updated is_report_published
            Cache::forget('admin_academic_years_list');
        }
    }

    /**
     * Check competency settings readiness for all subjects in a class.
     */
    private function getCompetencyReadiness(int $classId, int $academicYearId): array
    {
        $subjectRows = DB::select('
            SELECT DISTINCT
                sub.id AS subject_id,
                sub.name AS subject_name
            FROM schedules s
            INNER JOIN subjects sub ON sub.id = s.subject_id
            WHERE s.class_id = ? AND s.academic_year_id = ?
            ORDER BY sub.name
        ', [$classId, $academicYearId]);

        $configuredSubjectIds = SubjectCompetencySetting::where('academic_year_id', $academicYearId)
            ->pluck('subject_id')
            ->toArray();

        return array_map(function ($row) use ($configuredSubjectIds) {
            $isConfigured = in_array($row->subject_id, $configuredSubjectIds, true);

            return [
                'subject_id' => $row->subject_id,
                'subject' => $row->subject_name,
                'is_configured' => $isConfigured,
            ];
        }, $subjectRows);
    }

    private function getAttendanceReadiness(int $classId, int $academicYearId): array
    {
        $today = now()->toDateString();

        $rows = DB::select("
            SELECT
                s.id AS schedule_id,
                sub.name AS subject_name,
                u.name AS teacher_name,
                (SELECT COUNT(*) FROM meeting_sessions ms WHERE ms.schedule_id = s.id AND ms.date <= ? AND ms.status != 'holiday') AS total_meetings,
                (SELECT COUNT(DISTINCT ms2.id)
                 FROM meeting_sessions ms2
                 INNER JOIN attendances a ON a.meeting_session_id = ms2.id
                 WHERE ms2.schedule_id = s.id AND ms2.date <= ?
                ) AS meetings_with_attendance
            FROM schedules s
            INNER JOIN subjects sub ON sub.id = s.subject_id
            INNER JOIN teachers t ON t.user_id = s.teacher_id
            INNER JOIN users u ON u.id = t.user_id
            WHERE s.class_id = ? AND s.academic_year_id = ?
            ORDER BY sub.name
        ", [$today, $today, $classId, $academicYearId]);

        return array_map(function ($row) {
            $total = (int) $row->total_meetings;
            $completed = (int) $row->meetings_with_attendance;

            return [
                'schedule_id' => $row->schedule_id,
                'subject' => $row->subject_name,
                'teacher' => $row->teacher_name,
                'total_meetings' => $total,
                'meetings_with_attendance' => $completed,
                'is_complete' => $total > 0 && $completed >= $total,
                'missing_meetings' => max(0, $total - $completed),
            ];
        }, $rows);
    }

    private function getGradeReadiness(int $classId, int $academicYearId): array
    {
        $rows = DB::select('
            SELECT
                s.id AS schedule_id,
                sub.name AS subject_name,
                u.name AS teacher_name,
                (SELECT COUNT(*) FROM assignments a WHERE a.schedule_id = s.id) AS total_assignments,
                (SELECT COUNT(DISTINCT a2.id)
                 FROM assignments a2
                 INNER JOIN submissions sub2 ON sub2.assignment_id = a2.id
                 INNER JOIN grades g ON g.submission_id = sub2.id
                 WHERE a2.schedule_id = s.id
                ) AS graded_count
            FROM schedules s
            INNER JOIN subjects sub ON sub.id = s.subject_id
            INNER JOIN teachers t ON t.user_id = s.teacher_id
            INNER JOIN users u ON u.id = t.user_id
            WHERE s.class_id = ? AND s.academic_year_id = ?
            ORDER BY sub.name
        ', [$classId, $academicYearId]);

        return array_map(function ($row) {
            $total = (int) $row->total_assignments;
            $graded = (int) $row->graded_count;

            return [
                'schedule_id' => $row->schedule_id,
                'subject' => $row->subject_name,
                'teacher' => $row->teacher_name,
                'total_assignments' => $total,
                'graded_count' => $graded,
                'is_complete' => $total > 0 && $graded >= $total,
                'missing_count' => max(0, $total - $graded),
            ];
        }, $rows);
    }

    private function getStudentReadiness(int $classId, int $academicYearId): array
    {
        $rows = DB::select('
            SELECT
                u.id AS user_id,
                u.name,
                s.nis,
                CASE WHEN (
                    SELECT COUNT(DISTINCT sch2.id)
                    FROM schedules sch2
                    WHERE sch2.class_id = ? AND sch2.academic_year_id = ?
                ) = 0 THEN 1
                WHEN (
                    SELECT COUNT(DISTINCT ms.id)
                    FROM meeting_sessions ms
                    INNER JOIN attendances a ON a.meeting_session_id = ms.id
                    WHERE ms.schedule_id IN (
                        SELECT sch3.id FROM schedules sch3
                        WHERE sch3.class_id = ? AND sch3.academic_year_id = ?
                    )
                    AND a.student_id = u.id
                ) >= (
                    SELECT COUNT(DISTINCT sch4.id)
                    FROM schedules sch4
                    WHERE sch4.class_id = ? AND sch4.academic_year_id = ?
                ) THEN 1
                ELSE 0
                END AS attendance_ready,
                CASE WHEN (
                    SELECT COUNT(DISTINCT a2.id)
                    FROM assignments a2
                    INNER JOIN schedules sch5 ON sch5.id = a2.schedule_id
                    WHERE sch5.class_id = ? AND sch5.academic_year_id = ?
                ) = 0 THEN 1
                WHEN (
                    SELECT COUNT(DISTINCT g.id)
                    FROM grades g
                    INNER JOIN submissions sub ON sub.id = g.submission_id
                    INNER JOIN assignments a3 ON a3.id = sub.assignment_id
                    INNER JOIN schedules sch6 ON sch6.id = a3.schedule_id
                    WHERE sch6.class_id = ? AND sch6.academic_year_id = ?
                    AND sub.student_id = u.id
                ) >= (
                    SELECT COUNT(DISTINCT a4.id)
                    FROM assignments a4
                    INNER JOIN schedules sch7 ON sch7.id = a4.schedule_id
                    WHERE sch7.class_id = ? AND sch7.academic_year_id = ?
                ) THEN 1
                ELSE 0
                END AS grades_ready
            FROM class_student cs
            INNER JOIN users u ON u.id = cs.student_id
            INNER JOIN students s ON s.user_id = u.id
            WHERE cs.class_id = ?
            ORDER BY u.name
        ', [
            $classId, $academicYearId,
            $classId, $academicYearId,
            $classId, $academicYearId,
            $classId, $academicYearId,
            $classId, $academicYearId,
            $classId, $academicYearId,
            $classId,
        ]);

        // Check if all subjects in this class have competency settings configured
        $subjectIds = DB::table('schedules')
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->distinct()
            ->pluck('subject_id');

        $configuredCount = SubjectCompetencySetting::where('academic_year_id', $academicYearId)
            ->whereIn('subject_id', $subjectIds)
            ->count('subject_id');

        $allCompetencyConfigured = $subjectIds->isNotEmpty() && $configuredCount >= $subjectIds->count();

        $notReady = [];
        foreach ($rows as $row) {
            if (! $row->attendance_ready || ! $row->grades_ready || ! $allCompetencyConfigured) {
                $missing = [];
                if (! $row->attendance_ready) {
                    $missing[] = 'Kehadiran belum direkap';
                }
                if (! $row->grades_ready) {
                    $missing[] = 'Nilai belum diisi';
                }
                if (! $allCompetencyConfigured) {
                    $missing[] = 'Capaian kompetensi belum dikonfigurasi untuk semua mapel';
                }
                $notReady[] = [
                    'student_id' => $row->user_id,
                    'name' => $row->name,
                    'nis' => $row->nis ?? '-',
                    'missing' => $missing,
                ];
            }
        }

        $total = count($rows);
        $readyCount = $total - count($notReady);

        return [
            'total_count' => $total,
            'ready_count' => $readyCount,
            'not_ready_count' => count($notReady),
            'not_ready' => $notReady,
        ];
    }
}
