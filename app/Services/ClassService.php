<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClassService
{
    /**
     * Memasukkan banyak siswa ke dalam satu kelas sekaligus.
     */
    public function assignStudents(SchoolClass $schoolClass, array $studentIds): void
    {
        DB::beginTransaction();

        try {
            // Validasi Lifecycle: Pastikan HANYA siswa berstatus 'active' yang bisa dimasukkan
            $invalidStudents = Student::whereIn('user_id', $studentIds)
                ->where('status', '!=', 'active')
                ->count();

            if ($invalidStudents > 0) {
                throw new HttpException(422, "Terdapat siswa dengan status non-aktif (Alumni/Keluar) dalam daftar. Proses dibatalkan.");
            }

            // Lakukan update massal (Bulk Update) agar performa database tetap ringan
            Student::whereIn('user_id', $studentIds)->update([
                'class_id' => $schoolClass->id
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Menetapkan Guru Wali Kelas
     */
    public function assignTeacher(SchoolClass $schoolClass, int $teacherId): SchoolClass
    {
        $schoolClass->update([
            'homeroom_teacher_id' => $teacherId
        ]);

        return $schoolClass->load('homeroomTeacher.user');
    }
}