<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Holiday;
use App\Models\MeetingSession;
use App\Models\Schedule;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScheduleService
{
    /**
     * Create one or more schedules from form data.
     * Supports multi-day: if $data['days'] is an array, creates a schedule per day.
     * Dates are always inherited from the active academic year — not from the form.
     */
    public function createSchedule(array $data): array
    {
        if (empty($data['academic_year_id'])) {
            $activeYear = AcademicYear::query()->where('is_active', true)->first();
            if (! $activeYear) {
                throw new HttpException(400, 'Tidak ada Tahun Ajaran yang aktif. Silakan set Tahun Ajaran terlebih dahulu.');
            }
            $data['academic_year_id'] = $activeYear->id;
        }

        $days = $data['days'] ?? [$data['day_of_week'] ?? null];
        $days = array_filter($days);

        if (empty($days)) {
            throw new HttpException(422, 'Pilih minimal satu hari untuk jadwal.');
        }

        $createdSchedules = [];

        DB::transaction(function () use ($data, $days, &$createdSchedules) {
            foreach ($days as $day) {
                $scheduleData = array_merge($data, [
                    'day_of_week' => $day,
                ]);

                unset($scheduleData['days']);

                $this->validateClash($scheduleData);

                $schedule = Schedule::create($scheduleData);
                $this->generateMeetingSessions($schedule);

                $createdSchedules[] = $schedule;
            }
        });

        return $createdSchedules;
    }

    /**
     * Update a schedule. Supports two modes:
     * 1. Full edit (no data): change day, time, teacher, subject — all fields
     * 2. Teacher-only change (has data): only teacher_id can be changed
     */
    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        $hasData = $schedule->attendances()->exists()
            || $schedule->meetingSessions()->whereHas('attendances')->exists();

        if ($hasData) {
            // Only teacher_id change is allowed when schedule has data
            $allowedFields = ['teacher_id'];
            $requestedFields = array_keys($data);
            $disallowedFields = array_diff($requestedFields, $allowedFields);

            if (! empty($disallowedFields)) {
                throw new HttpException(422, 'Jadwal ini sudah memiliki data absensi. Hanya penggantian guru yang diizinkan.');
            }

            // Validate teacher clash for the new teacher
            if (isset($data['teacher_id']) && $data['teacher_id'] !== $schedule->teacher_id) {
                $teacherClash = Schedule::where('academic_year_id', $schedule->academic_year_id)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->where('id', '!=', $schedule->id)
                    ->where('teacher_id', $data['teacher_id'])
                    ->where(function ($q) use ($schedule) {
                        $q->where('start_time', '<', $schedule->end_time)
                            ->where('end_time', '>', $schedule->start_time);
                    })
                    ->exists();

                if ($teacherClash) {
                    throw new HttpException(422, 'Bentrok: Guru ini sudah dijadwalkan mengajar pada waktu tersebut.');
                }
            }

            $schedule->fill($data)->save();

            return $schedule;
        }

        // Full edit mode (no data yet)
        $data['academic_year_id'] = $schedule->academic_year_id;

        $hasData = $schedule->attendances()->exists()
            || $schedule->meetingSessions()->whereHas('attendances')->exists();

        if ($hasData) {
            // Only teacher_id change is allowed when schedule has data
            $allowedFields = ['teacher_id'];
            $requestedFields = array_keys($data);
            $disallowedFields = array_diff($requestedFields, $allowedFields);

            if (! empty($disallowedFields)) {
                throw new HttpException(422, 'Jadwal ini sudah memiliki data absensi. Hanya penggantian guru yang diizinkan.');
            }

            // Validate teacher clash for the new teacher
            if (isset($data['teacher_id']) && $data['teacher_id'] !== $schedule->teacher_id) {
                $teacherClash = Schedule::where('academic_year_id', $schedule->academic_year_id)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->where('id', '!=', $schedule->id)
                    ->where('teacher_id', $data['teacher_id'])
                    ->where(function ($q) use ($schedule) {
                        $q->where('start_time', '<', $schedule->end_time)
                            ->where('end_time', '>', $schedule->start_time);
                    })
                    ->exists();

                if ($teacherClash) {
                    throw new HttpException(422, 'Bentrok: Guru ini sudah dijadwalkan mengajar pada waktu tersebut.');
                }
            }

            $schedule->fill($data)->save();

            return $schedule;
        }

        // Full edit mode (no data yet)
        $day = $data['day_of_week'] ?? $schedule->day_of_week;
        $this->validateClash(array_merge($data, ['day_of_week' => $day]), $schedule->id);

        return DB::transaction(function () use ($schedule, $data) {
            $dayChanged = ($data['day_of_week'] ?? $schedule->day_of_week) !== $schedule->day_of_week;

            $schedule->fill($data)->save();

            if ($dayChanged) {
                $this->regenerateMeetingSessions($schedule);
            }

            return $schedule;
        });
    }

    /**
     * Swap day/time between two schedules.
     * Both schedules must be in the same academic year.
     * Blocked if either has upcoming meeting sessions with attendance data.
     */
    public function swapSchedules(Schedule $scheduleA, Schedule $scheduleB): array
    {
        if ($scheduleA->academic_year_id !== $scheduleB->academic_year_id) {
            throw new HttpException(422, 'Kedua jadwal harus berada di tahun ajaran yang sama.');
        }

        if ($scheduleA->id === $scheduleB->id) {
            throw new HttpException(422, 'Tidak bisa menukar jadwal dengan dirinya sendiri.');
        }

        // Check if either schedule has upcoming sessions with attendance data
        $today = now()->toDateString();

        $blockedA = $scheduleA->meetingSessions()
            ->where('date', '>=', $today)
            ->whereHas('attendances')
            ->exists();

        $blockedB = $scheduleB->meetingSessions()
            ->where('date', '>=', $today)
            ->whereHas('attendances')
            ->exists();

        if ($blockedA || $blockedB) {
            throw new HttpException(422, 'Tidak bisa menukar jadwal: salah satu sudah memiliki data absensi di pertemuan mendatang. Tunggu hingga minggu depan.');
        }

        // Swap in a transaction
        return DB::transaction(function () use ($scheduleA, $scheduleB) {
            // Temporarily store A's time values
            $tempDay = $scheduleA->day_of_week;
            $tempStart = $scheduleA->start_time;
            $tempEnd = $scheduleA->end_time;

            // A gets B's time
            $scheduleA->update([
                'day_of_week' => $scheduleB->day_of_week,
                'start_time' => $scheduleB->start_time,
                'end_time' => $scheduleB->end_time,
            ]);

            // B gets A's old time
            $scheduleB->update([
                'day_of_week' => $tempDay,
                'start_time' => $tempStart,
                'end_time' => $tempEnd,
            ]);

            // Regenerate meeting sessions for both
            $this->regenerateMeetingSessions($scheduleA);
            $this->regenerateMeetingSessions($scheduleB);

            return [$scheduleA->fresh(), $scheduleB->fresh()];
        });
    }

    /**
     * Generate meeting sessions for a schedule based on its academic year's dates.
     * Skips published classes (sessions are frozen on publish).
     */
    public function generateMeetingSessions(Schedule $schedule): void
    {
        $academicYear = AcademicYear::query()->findOrFail($schedule->academic_year_id);

        // Skip if the class is published — sessions are frozen
        $schoolClass = SchoolClass::query()->find($schedule->class_id);
        if ($schoolClass && $schoolClass->is_published) {
            return;
        }

        // Always use academic year dates (schedule dates are inherited, not stored)
        $startDate = $academicYear->start_date ? $academicYear->start_date->copy()->startOfDay() : null;
        $endDate = $academicYear->end_date ? $academicYear->end_date->copy()->endOfDay() : null;

        if (! $startDate || ! $endDate) {
            return;
        }

        $dayOfWeek = $schedule->day_of_week;

        $existingMax = MeetingSession::query()
            ->where('schedule_id', $schedule->id)
            ->max('meeting_number') ?? 0;

        $meetingNumber = $existingMax + 1;
        $current = $startDate->copy()->modify("next {$dayOfWeek}");

        if ($current->lt($startDate)) {
            $current->addWeek();
        }

        $holidayDates = Holiday::query()->pluck('date')->map->toDateString()->toArray();
        $sessionsToInsert = [];

        while ($current->lte($endDate)) {
            $dateString = $current->toDateString();
            $status = in_array($dateString, $holidayDates) ? 'holiday' : 'scheduled';

            $sessionsToInsert[] = [
                'schedule_id' => $schedule->id,
                'meeting_number' => $meetingNumber,
                'date' => $dateString,
                'status' => $status,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $meetingNumber++;
            $current->addWeek();
        }

        if ($sessionsToInsert !== []) {
            DB::table('meeting_sessions')->insert($sessionsToInsert);
        }
    }

    /**
     * Delete uncompleted sessions and regenerate from scratch.
     */
    public function regenerateMeetingSessions(Schedule $schedule): void
    {
        MeetingSession::query()
            ->where('schedule_id', $schedule->id)
            ->whereDoesntHave('attendances')
            ->delete();

        $this->generateMeetingSessions($schedule);
    }

    private function validateClash(array $data, ?int $ignoreScheduleId = null): void
    {
        $clashQuery = Schedule::query()->where('academic_year_id', $data['academic_year_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($query) use ($data) {
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            });

        if ($ignoreScheduleId) {
            $clashQuery->where('id', '!=', $ignoreScheduleId);
        }

        $classClash = (clone $clashQuery)->where('class_id', $data['class_id'])->exists();
        if ($classClash) {
            throw new HttpException(422, 'Bentrok: Kelas ini sudah memiliki jadwal pelajaran lain pada waktu tersebut.');
        }

        $teacherClash = (clone $clashQuery)->where('teacher_id', $data['teacher_id'])->exists();
        if ($teacherClash) {
            throw new HttpException(422, 'Bentrok: Guru ini sudah dijadwalkan mengajar di kelas lain pada waktu tersebut.');
        }
    }
}
