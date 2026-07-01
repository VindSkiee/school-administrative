<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Submission;
use App\Notifications\SubmissionGraded;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GradeService
{
    public function gradeSubmission(int $teacherId, int $submissionId, array $data): Grade
    {
        // Validasi Rantai Kepemilikan (Chain of Ownership)
        $submission = Submission::with('assignment.schedule.schoolClass')->findOrFail($submissionId);
        $schedule = $submission->assignment->schedule;

        // Cek apakah kelas sudah dipublikasikan
        $schoolClass = $schedule->schoolClass;
        if ($schoolClass && $schoolClass->is_published) {
            throw new HttpException(403, 'Kelas ini sudah dipublikasikan. Anda tidak dapat lagi mengubah nilai.');
        }
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
