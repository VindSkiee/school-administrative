<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Schedule;
use App\Models\Student;
use App\Notifications\AttendanceRequestReviewed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AttendanceRequestService
{
    public function submitRequest(int $studentId, array $data, UploadedFile $file): AttendanceRequest
    {
        $student = Student::query()->findOrFail($studentId);
        $schedule = Schedule::query()->findOrFail($data['schedule_id']);

        if (! $student->class_id) {
            throw new HttpException(422, 'Siswa belum terdaftar pada kelas.');
        }

        if ((int) $schedule->class_id !== (int) $student->class_id) {
            throw new HttpException(422, 'Jadwal tidak sesuai dengan kelas siswa.');
        }

        // Tambahkan query() agar Intelephense mengenali Builder
        $exists = AttendanceRequest::query()
            ->where('student_id', $studentId)
            ->where('schedule_id', $data['schedule_id'])
            ->where('date', $data['date'])
            ->exists();

        if ($exists) {
            throw new HttpException(422, 'Anda sudah mengajukan izin/sakit untuk jadwal ini.');
        }

        // Simpan file
        $path = $file->store('attendance_proofs', 'public');
        $data['proof_file_path'] = $path;
        $data['student_id'] = $studentId;
        $data['status'] = 'pending';

        // Tambahkan query()
        return AttendanceRequest::query()->create($data);
    }

    public function reviewRequest(int $teacherId, AttendanceRequest $request, string $status): AttendanceRequest
    {
        if ($request->status !== 'pending') {
            throw new HttpException(422, 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // Gunakan query()->findOrFail() agar IDE aman dan logika lebih solid
        $schedule = Schedule::query()->findOrFail($request->schedule_id);
        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak: Anda bukan guru pengampu jadwal ini.');
        }

        DB::beginTransaction();

        try {
            // Gunakan property assignment & save() untuk menghindari error Intelephense pada metode update()
            $request->status = $status;
            $request->approved_by = $teacherId;
            $request->save();

            // Side-Effect: Jika disetujui, otomatis rekap ke tabel kehadiran utama
            if ($status === 'approved') {
                // Tambahkan query()
                Attendance::query()->updateOrCreate(
                    [
                        'schedule_id' => $request->schedule_id,
                        'student_id' => $request->student_id,
                        'date' => $request->date,
                    ],
                    [
                        'status' => $request->type, // 'sick' atau 'permission'
                    ]
                );
            }

            DB::commit();

            // Kirim notifikasi
            $studentUser = $request->student->user;
            $studentUser->notify(new AttendanceRequestReviewed($status, $request->date));

            return $request;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpException(500, 'Gagal memproses pengajuan: '.$e->getMessage());
        }
    }
}
