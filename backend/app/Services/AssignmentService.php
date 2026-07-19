<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\GradingSetting;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SubjectCompetencySetting;
use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssignmentService
{
    // --- AREA GURU ---
    public function createAssignment(int $teacherId, array $data, ?array $files): Assignment
    {
        $schedule = Schedule::query()->findOrFail($data['schedule_id']);

        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak: Anda tidak mengajar di jadwal ini.');
        }

        $paths = [];
        if ($files) {
            foreach ($files as $file) {
                $paths[] = $file->store('assignments', 'public');
            }
        }
        $data['attachments'] = $paths;

        // Strip frontend-only fields
        unset($data['enable_remedial'], $data['remedial_mode']);

        return Assignment::query()->create($data);
    }

    public function deleteAssignment(int $teacherId, Assignment $assignment): void
    {
        $schedule = Schedule::query()->findOrFail($assignment->schedule_id);

        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak.');
        }

        // Delete assignment attachments
        if (is_array($assignment->attachments)) {
            foreach ($assignment->attachments as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        // Delete student submission files before cascade
        $submissionPaths = $assignment->submissions()->pluck('file_path')->filter();
        foreach ($submissionPaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $assignment->delete();
    }

    /**
     * Get students from the same class whose graded score for a specific exam type is below KKM.
     * KKM is resolved per-subject (SubjectCompetencySetting.min_score), falling back to global.
     * Excludes students who already have a remedial assignment for this exam.
     *
     * @return array<int, array{id:int, name:string, nis:string, score:float|null}>
     */
    public function getStudentsBelowKKM(int $teacherId, int $assignmentId): array
    {
        $assignment = Assignment::with('schedule')->findOrFail($assignmentId);

        if ($assignment->schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak.');
        }

        $academicYearId = $assignment->schedule->academic_year_id;
        $classId = $assignment->schedule->class_id;

        // Resolve KKM: prefer subject-level, fallback to global
        $kkm = $this->resolveSubjectKKM(
            $assignment->schedule->subject_id,
            $academicYearId
        );

        // Get students in this class
        $students = Student::with(['user:id,name'])
            ->whereHas('classes', fn ($q) => $q->where('classes.id', $classId))
            ->where('status', 'active')
            ->orderBy('nisn')
            ->get();

        // Get existing remedial assignment IDs for this parent
        $remedialAssignmentIds = Assignment::where('linked_assignment_id', $assignmentId)
            ->pluck('id');

        // PERF FIX: Bulk fetch ALL submissions for this assignment in ONE query
        $submissionMap = Submission::where('assignment_id', $assignmentId)
            ->with('grade:id,submission_id,score')
            ->get()
            ->keyBy('student_id');

        // PERF FIX: Bulk fetch ALL remedial submission student_ids in ONE query
        $remedialStudentIds = collect();
        if ($remedialAssignmentIds->isNotEmpty()) {
            $remedialStudentIds = Submission::whereIn('assignment_id', $remedialAssignmentIds)
                ->pluck('student_id')
                ->flip();
        }

        $result = [];
        foreach ($students as $student) {
            $submission = $submissionMap->get($student->user_id);
            $score = $submission?->grade?->score;

            if ($score !== null && (float) $score < $kkm) {
                $result[] = [
                    'id' => $student->user_id,
                    'name' => $student->user?->name ?? 'Tanpa Nama',
                    'nis' => $student->nis ?? '-',
                    'score' => (float) $score,
                    'has_remedial' => $remedialStudentIds->has($student->user_id),
                ];
            }
        }

        return $result;
    }

    /**
     * Create remedial assignments for students below KKM.
     * Creates a single remedial assignment + auto-creates empty submissions for each target student.
     *
     * @param  array<int, int>  $studentIds
     */
    public function createRemedialAssignments(
        int $teacherId,
        int $parentAssignmentId,
        array $studentIds,
        ?string $remedialMode = null,
    ): Assignment {
        $parent = Assignment::with('schedule')->findOrFail($parentAssignmentId);

        if ($parent->schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak.');
        }

        if (! in_array($parent->type, ['ujian_harian', 'uts', 'uas'])) {
            throw new HttpException(422, 'Remedial hanya dapat dibuat untuk tipe ujian.');
        }

        $academicYear = $parent->schedule->academicYear;
        $kkm = $this->resolveSubjectKKM(
            $parent->schedule->subject_id,
            $parent->schedule->academic_year_id
        );

        return DB::transaction(function () use ($parent, $studentIds, $remedialMode, $kkm) {
            // Create the remedial assignment
            $remedial = Assignment::create([
                'schedule_id' => $parent->schedule_id,
                'type' => $parent->type,
                'date' => now()->format('Y-m-d'),
                'title' => "{$parent->title} — Remedial",
                'description' => "Remedial untuk: {$parent->title}\nBatas KKM: {$kkm}\nSiswa yang mendapat remedial: ".count($studentIds).' orang.',
                'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'attachments' => $parent->attachments ?? [],
                'is_remedial' => true,
                'remedial_for_type' => $parent->type,
                'linked_assignment_id' => $parent->id,
            ]);

            // PERF FIX: Bulk create all submissions in ONE query (replaces N× firstOrCreate)
            $now = now()->toDateTimeString();
            $insertRows = array_map(fn ($studentId) => [
                'assignment_id' => $remedial->id,
                'student_id' => $studentId,
                'file_path' => null,
                'submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ], $studentIds);

            if (! empty($insertRows)) {
                DB::table('submissions')->insertOrIgnore($insertRows);
            }

            // PERF FIX: Bulk update parent grades' remedial_mode in ONE query (replaces N× find+update)
            if ($remedialMode) {
                $parentSubmissionIds = Submission::where('assignment_id', $parent->id)
                    ->whereIn('student_id', $studentIds)
                    ->whereHas('grade')
                    ->pluck('id');

                if ($parentSubmissionIds->isNotEmpty()) {
                    DB::table('grades')
                        ->whereIn('submission_id', $parentSubmissionIds)
                        ->update([
                            'remedial_mode' => $remedialMode,
                            'updated_at' => $now,
                        ]);
                }
            }

            return $remedial;
        });
    }

    // --- AREA SISWA ---
    public function submitAssignment(int $studentId, int $classId, int $assignmentId, UploadedFile $file): Submission
    {
        $assignment = Assignment::with('schedule')->findOrFail($assignmentId);

        if ($assignment->schedule->class_id !== $classId) {
            throw new HttpException(403, 'Akses ditolak: Tugas ini bukan untuk kelas Anda.');
        }

        if (Carbon::now()->isAfter($assignment->due_date)) {
            throw new HttpException(422, 'Tenggat waktu pengumpulan tugas telah lewat.');
        }

        $existingSubmission = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existingSubmission && Storage::disk('public')->exists($existingSubmission->file_path)) {
            Storage::disk('public')->delete($existingSubmission->file_path);
        }

        $path = $file->store('submissions', 'public');
        $now = Carbon::now();
        $isLate = $now->isAfter($assignment->due_date);

        if ($existingSubmission) {
            $existingSubmission->update([
                'file_path' => $path,
                'edited_at' => $now,
                'is_late' => $existingSubmission->is_late || $isLate,
            ]);

            return $existingSubmission;
        }

        return Submission::query()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $studentId,
            'file_path' => $path,
            'submitted_at' => $now,
            'is_late' => $isLate,
        ]);
    }

    /**
     * Resolve KKM for a subject: prefer SubjectCompetencySetting.min_score, fallback to global.
     */
    private function resolveSubjectKKM(int $subjectId, int $academicYearId): int
    {
        $setting = SubjectCompetencySetting::where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if ($setting && $setting->min_score !== null) {
            return (int) $setting->min_score;
        }

        // Fallback to global
        $gradingSetting = GradingSetting::where('academic_year_id', $academicYearId)->first();

        return $gradingSetting?->min_score_to_pass ?? 60;
    }
}
