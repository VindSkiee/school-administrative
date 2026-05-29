<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    protected $primaryKey = 'user_id';

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

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_student', 'student_id', 'class_id')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }
}
