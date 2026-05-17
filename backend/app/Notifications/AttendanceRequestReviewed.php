<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceRequestReviewed extends Notification
{
    use Queueable;

    public function __construct(
        public string $status, // 'approved' atau 'rejected'
        public string $date
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusIndo = $this->status === 'approved' ? 'disetujui' : 'ditolak';

        return [
            'type' => 'attendance_request_reviewed',
            'title' => 'Status Pengajuan Absen',
            'message' => "Pengajuan izin/sakit Anda untuk tanggal {$this->date} telah {$statusIndo}.",
            'date' => $this->date,
            'url' => '/student/attendance-requests',
        ];
    }
}
