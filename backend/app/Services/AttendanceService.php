<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AttendanceService
{
    public function storeBulkAttendance(int $teacherId, array $data): void
    {
        // 1. Otorisasi Ketat (Teacher Scope Validation)
        $schedule = Schedule::query()->findOrFail($data['schedule_id']);

        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak: Anda tidak memiliki hak untuk mengisi absensi pada jadwal ini.');
        }

        $studentIds = collect($data['attendances'])
            ->pluck('student_id')
            ->filter()
            ->unique();

        if ($studentIds->isNotEmpty()) {
            $validStudentIds = Student::query()
                ->whereIn('user_id', $studentIds->all()) // Disederhanakan
                // PERBAIKAN: Gunakan whereHas alih-alih where('class_id', ...)
                ->whereHas('classes', function ($query) use ($schedule) {
                    $query->where('classes.id', $schedule->class_id);
                })
                ->pluck('user_id')
                ->all();

            $invalidStudentIds = $studentIds->diff($validStudentIds);

            if ($invalidStudentIds->isNotEmpty()) {
                throw new HttpException(422, 'Beberapa siswa tidak terdaftar di kelas pada jadwal ini.');
            }
        }

        DB::beginTransaction();

        try {
            foreach ($data['attendances'] as $item) {
                // updateOrCreate mencegah duplikasi jika guru merevisi absen di hari yang sama
                Attendance::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'student_id' => $item['student_id'],
                        'date' => $data['date'],
                    ],
                    [
                        'status' => $item['status'],
                    ]
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpException(500, 'Gagal menyimpan data absensi: '.$e->getMessage());
        }
    }
}
