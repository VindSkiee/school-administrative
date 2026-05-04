<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'class_id', 'subject_id', 'teacher_id', 'academic_year_id', 
        'day_of_week', 'start_time', 'end_time'
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class, 'teacher_id', 'user_id'); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function assignments(): HasMany { return $this->hasMany(Assignment::class); }
    public function materials(): HasMany { return $this->hasMany(Material::class); }
}