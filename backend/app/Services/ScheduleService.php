<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Schedule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScheduleService
{
    public function createSchedule(array $data): Schedule
    {
        // 1. Gunakan academic_year_id dari request (sudah divalidasi di FormRequest)
        if (empty($data['academic_year_id'])) {
            $activeYear = AcademicYear::query()->where('is_active', true)->first();
            if (! $activeYear) {
                throw new HttpException(400, 'Tidak ada Tahun Ajaran yang aktif. Silakan set Tahun Ajaran terlebih dahulu.');
            }
            $data['academic_year_id'] = $activeYear->id;
        }

        // 2. Cek Bentrok Jadwal (Clash Detection)
        $this->validateClash($data);

        // 3. Simpan Jadwal
        return Schedule::create($data);
    }

    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        $data['academic_year_id'] = $schedule->academic_year_id;

        // Pengecekan clash dengan mengabaikan jadwal yang sedang di-edit ini
        $this->validateClash($data, $schedule->id);

        $schedule->fill($data)->save();

        return $schedule;
    }

    private function validateClash(array $data, ?int $ignoreScheduleId = null): void
    {
        $clashQuery = Schedule::query()->where('academic_year_id', $data['academic_year_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($query) use ($data) {
                // Rumus Overlap: StartA < EndB AND EndA > StartB
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            });

        if ($ignoreScheduleId) {
            $clashQuery->where('id', '!=', $ignoreScheduleId);
        }

        // Cek Bentrok Kelas
        $classClash = (clone $clashQuery)->where('class_id', $data['class_id'])->exists();
        if ($classClash) {
            throw new HttpException(422, 'Bentrok: Kelas ini sudah memiliki jadwal pelajaran lain pada waktu tersebut.');
        }

        // Cek Bentrok Guru
        $teacherClash = (clone $clashQuery)->where('teacher_id', $data['teacher_id'])->exists();
        if ($teacherClash) {
            throw new HttpException(422, 'Bentrok: Guru ini sudah dijadwalkan mengajar di kelas lain pada waktu tersebut.');
        }
    }
}
