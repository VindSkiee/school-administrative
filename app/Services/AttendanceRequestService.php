<?php

namespace App\Services;

use App\Models\AttendanceRequest;
use App\Models\Attendance;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AttendanceRequestService
{
    public function submitRequest(int $studentId, array $data, UploadedFile $file): AttendanceRequest
    {
        // Cegah duplikasi request di hari dan jadwal yang sama
        $exists = AttendanceRequest::where('student_id', $studentId)
            ->where('schedule_id', $data['schedule_id'])
            ->where('date', $data['date'])
            ->exists();

        if ($exists) {
            throw new HttpException(422, "Anda sudah mengajukan izin/sakit untuk jadwal ini.");
        }

        // Simpan file
        $path = $file->store('attendance_proofs', 'public');
        $data['proof_file_path'] = $path;
        $data['student_id'] = $studentId;
        $data['status'] = 'pending';

        return AttendanceRequest::create($data);
    }

    public function reviewRequest(int $teacherId, AttendanceRequest $request, string $status): AttendanceRequest
    {
        if ($request->status !== 'pending') {
            throw new HttpException(422, "Pengajuan ini sudah diproses sebelumnya.");
        }

        // Otorisasi: Pastikan guru ini adalah pemilik jadwal tersebut
        $schedule = Schedule::find($request->schedule_id);
        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, "Akses ditolak: Anda bukan guru pengampu jadwal ini.");
        }

        DB::beginTransaction();

        try {
            // Update status pengajuan
            $request->update([
                'status' => $status,
                'approved_by' => $teacherId
            ]);

            // Side-Effect: Jika disetujui, otomatis rekap ke tabel kehadiran utama
            if ($status === 'approved') {
                Attendance::updateOrCreate(
                    [
                        'schedule_id' => $request->schedule_id,
                        'student_id' => $request->student_id,
                        'date' => $request->date
                    ],
                    [
                        'status' => $request->type // 'sick' atau 'permission'
                    ]
                );
            }

            DB::commit();
            return $request;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpException(500, "Gagal memproses pengajuan: " . $e->getMessage());
        }
    }
}