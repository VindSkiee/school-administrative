<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\RecordsActivity;

class Student extends Model
{
    protected $primaryKey = 'user_id';

    use RecordsActivity;

    public $incrementing = false;

    // Tambahkan 'nis' ke dalam fillable
    protected $fillable = [
        'user_id',
        'nisn',
        'nis',
        'gender',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function attendances()
    {
        // Sesuaikan 'student_id' dengan nama kolom yang ada di tabel 'attendances' Anda
        return $this->hasMany(Attendance::class, 'student_id', 'user_id');
    }

    public function submissions()
    {
        // Sesuaikan 'student_id' dengan nama kolom yang ada di tabel 'submissions' Anda
        return $this->hasMany(Submission::class, 'student_id', 'user_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_student', 'student_id', 'class_id')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }
}
