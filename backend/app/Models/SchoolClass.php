<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RecordsActivity;

class SchoolClass extends Model
{
    use RecordsActivity;
    use SoftDeletes;

    protected $table = 'classes'; // Arahkan eksplisit ke tabel classes

    protected $fillable = [
        'name',
        'academic_year_id',
        'homeroom_teacher_id',
    ];

    /**
     * Relasi ke Tahun Ajaran (Wajib)
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Relasi ke Wali Kelas (Teacher)
     * Karena foreign key-nya 'homeroom_teacher_id' merujuk ke tabel teachers (user_id)
     */
    public function homeroomTeacher()
    {
        // Parameter 2: Foreign Key di tabel classes
        // Parameter 3: Primary Key/Owner Key di tabel teachers
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id', 'user_id');
    }

    /**
     * Relasi ke Siswa (1 Kelas memiliki banyak Siswa)
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student', 'class_id', 'student_id')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }

    /**
     * Relasi ke Jadwal Pelajaran (Untuk Phase 3)
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }
}
