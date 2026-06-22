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
                ->whereIn('user_id', $studentIds->all())
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
            // PERF FIX: replaced N updateOrCreate calls with single upsert() — requires MySQL 8.0+ / Laravel 8+
            $now = now();
            $rows = [];
            foreach ($data['attendances'] as $item) {
                $rows[] = [
                    'schedule_id' => $schedule->id,
                    'student_id' => $item['student_id'],
                    'date' => $data['date'],
                    'status' => $item['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // PERF FIX: single bulk upsert replaces N individual updateOrCreate calls
            Attendance::upsert(
                $rows,
                ['schedule_id', 'student_id', 'date'],  // unique key columns
                ['status', 'updated_at']                 // columns to update on conflict
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpException(500, 'Gagal menyimpan data absensi: '.$e->getMessage());
        }
    }
}
