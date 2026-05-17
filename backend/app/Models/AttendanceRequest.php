<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRequest extends Model
{
    protected $fillable = [
        'schedule_id', 'student_id', 'date', 'type', 'reason', 
        'proof_file_path', 'status', 'approved_by'
    ];

    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'student_id', 'user_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(Teacher::class, 'approved_by', 'user_id'); }
}