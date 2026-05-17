<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionGraded extends Notification
{
    use Queueable;

    public function __construct(
        public Assignment $assignment,
        public float|int $score
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Simpan ke database
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'submission_graded',
            'title' => 'Tugas Dinilai',
            'message' => "Tugas '{$this->assignment->title}' telah dinilai. Anda mendapat nilai {$this->score}.",
            'assignment_id' => $this->assignment->id,
            'url' => "/student/assignments/{$this->assignment->id}", // Untuk link Frontend
        ];
    }
}
