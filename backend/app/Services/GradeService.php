<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Submission;
use App\Notifications\SubmissionGraded;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\AcademicYear;

class GradeService
{
    public function gradeSubmission(int $teacherId, int $submissionId, array $data): Grade
    {
        // Tarik data submission sekaligus relasi induknya untuk validasi
        $academicYear = AcademicYear::query()->where('is_active', true)->first();
        if ($academicYear && $academicYear->is_report_published) {
            throw new HttpException(403, "Rapor semester ini telah diterbitkan. Anda tidak dapat lagi mengubah nilai.");
        }

        // Validasi Rantai Kepemilikan (Chain of Ownership)
        $submission = Submission::with('assignment.schedule')->findOrFail($submissionId);
        $schedule = $submission->assignment->schedule;
        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk menilai tugas di kelas ini.');
        }

        // Upsert data nilai (Update jika sudah ada, Create jika belum)
        $grade = Grade::query()->updateOrCreate(
            [
                'submission_id' => $submission->id,
            ],
            [
                'score' => $data['score'],
                'feedback' => $data['feedback'] ?? null,
                'graded_by' => $teacherId,
            ]
        );

        $studentUser = $submission->student->user;
        $studentUser->notify(new SubmissionGraded($submission->assignment, $data['score']));

        return $grade;
    }
}
