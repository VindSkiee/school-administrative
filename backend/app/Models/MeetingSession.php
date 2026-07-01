<?php

namespace App\Models;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingSession extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'schedule_id', 'meeting_number', 'date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meeting_number' => 'integer',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function isHoliday(): bool
    {
        return $this->status === 'holiday';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }
}
