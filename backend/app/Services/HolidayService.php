<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\MeetingSession;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HolidayService
{
    public function createHoliday(array $data): Holiday
    {
        $exists = Holiday::query()->where('date', $data['date'])->exists();

        if ($exists) {
            throw new HttpException(422, 'Tanggal tersebut sudah ditetapkan sebagai hari libur.');
        }

        return DB::transaction(function () use ($data) {
            $holiday = Holiday::query()->create($data);

            MeetingSession::query()
                ->whereDate('date', $holiday->date->toDateString())
                ->where('status', '!=', 'holiday')
                ->update(['status' => 'holiday']);

            return $holiday;
        });
    }

    public function deleteHoliday(Holiday $holiday): void
    {
        DB::transaction(function () use ($holiday) {
            MeetingSession::query()
                ->whereDate('date', $holiday->date->toDateString())
                ->where('status', 'holiday')
                ->update(['status' => 'scheduled']);

            $holiday->delete();
        });
    }
}
