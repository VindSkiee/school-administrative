<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingSetting extends Model
{
    protected $fillable = [
        'academic_year_id',
        'task_weight',
        'daily_exam_weight',
        'uts_weight',
        'uas_weight',
        'attendance_weight',
        'min_score_to_pass',
    ];

    protected $casts = [
        'task_weight' => 'integer',
        'daily_exam_weight' => 'integer',
        'uts_weight' => 'integer',
        'uas_weight' => 'integer',
        'attendance_weight' => 'integer',
        'min_score_to_pass' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
