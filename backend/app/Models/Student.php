<?php

namespace App\Models;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'eskul_selection_completed',
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

    public function studentEskuls()
    {
        return $this->hasMany(StudentEskul::class, 'student_id', 'user_id');
    }

    public function eskulChangeRequests()
    {
        return $this->hasMany(EskulChangeRequest::class, 'student_id', 'user_id');
    }

    public function eskuls()
    {
        return $this->belongsToMany(Eskul::class, 'student_eskuls', 'student_id', 'eskul_id')
            ->withPivot(['academic_year_id', 'score', 'description', 'graded_at', 'graded_by'])
            ->withTimestamps();
    }
}
