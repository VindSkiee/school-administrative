<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;

class AssignmentService
{
    // --- AREA GURU ---
    public function createAssignment(int $teacherId, array $data, ?UploadedFile $file): Assignment
    {
        $schedule = Schedule::query()->findOrFail($data['schedule_id']);

        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, "Akses ditolak: Anda tidak mengajar di jadwal ini.");
        }

        if ($file) {
            $data['file_path'] = $file->store('assignments', 'public');
        }

        return Assignment::query()->create($data);
    }

    // --- AREA SISWA ---
    public function submitAssignment(int $studentId, int $classId, int $assignmentId, UploadedFile $file): Submission
    {
        $assignment = Assignment::with('schedule')->findOrFail($assignmentId);

        // 1. Validasi Kelas (IDOR Prevention)
        if ($assignment->schedule->class_id !== $classId) {
            throw new HttpException(403, "Akses ditolak: Tugas ini bukan untuk kelas Anda.");
        }

        // 2. Validasi Deadline
        if (Carbon::now()->isAfter($assignment->due_date)) {
            throw new HttpException(422, "Tenggat waktu pengumpulan tugas telah lewat.");
        }

        // Cek apakah siswa sudah pernah submit (untuk menghapus file lama jika ada)
        $existingSubmission = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existingSubmission && Storage::disk('public')->exists($existingSubmission->file_path)) {
            Storage::disk('public')->delete($existingSubmission->file_path);
        }

        // Simpan file baru
        $path = $file->store('submissions', 'public');

        // Gunakan updateOrCreate untuk Insert atau Update
        return Submission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
            ],
            [
                'file_path' => $path,
                'submitted_at' => now(),
            ]
        );
    }
}