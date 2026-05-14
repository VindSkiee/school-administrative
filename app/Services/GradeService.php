<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Submission;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GradeService
{
    public function gradeSubmission(int $teacherId, int $submissionId, array $data): Grade
    {
        // Tarik data submission sekaligus relasi induknya untuk validasi
        $submission = Submission::with('assignment.schedule')->findOrFail($submissionId);

        // Validasi Rantai Kepemilikan (Chain of Ownership)
        $schedule = $submission->assignment->schedule;
        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, "Akses ditolak: Anda tidak memiliki wewenang untuk menilai tugas di kelas ini.");
        }

        // Upsert data nilai (Update jika sudah ada, Create jika belum)
        return Grade::query()->updateOrCreate(
            [
                'submission_id' => $submission->id,
            ],
            [
                'score' => $data['score'],
                'feedback' => $data['feedback'] ?? null,
                'graded_by' => $teacherId,
            ]
        );
    }
}