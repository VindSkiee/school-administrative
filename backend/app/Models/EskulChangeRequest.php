<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EskulChangeRequest extends Model
{
    protected $fillable = [
        'student_id',
        'current_eskul_id',
        'requested_eskul_id',
        'academic_year_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }

    public function currentEskul(): BelongsTo
    {
        return $this->belongsTo(Eskul::class, 'current_eskul_id');
    }

    public function requestedEskul(): BelongsTo
    {
        return $this->belongsTo(Eskul::class, 'requested_eskul_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
