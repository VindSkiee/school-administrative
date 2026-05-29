<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use Exception;
use Illuminate\Support\Facades\DB;
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
            // 1. Validasi Lifecycle: Pastikan HANYA siswa berstatus 'active' yang bisa dimasukkan
            $invalidStudents = Student::query()
                ->whereIn('user_id', $studentIds)
                ->where('status', '!=', 'active')
                ->count();

            if ($invalidStudents > 0) {
                throw new HttpException(422, 'Terdapat siswa dengan status non-aktif (Alumni/Keluar) dalam daftar. Proses dibatalkan.');
            }

            // 2. LOGIKA BARU: Siapkan data Pivot (Menyertakan academic_year_id)
            $academicYearId = $schoolClass->academic_year_id;
            $conflictingStudentIds = DB::table('class_student')
                ->whereIn('student_id', $studentIds)
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', '!=', $schoolClass->id)
                ->distinct()
                ->pluck('student_id')
                ->all();

            if (! empty($conflictingStudentIds)) {
                throw new HttpException(422, 'Siswa sudah terdaftar di kelas lain pada tahun ajaran yang sama. Proses dibatalkan.');
            }

            $syncData = [];

            foreach ($studentIds as $id) {
                // Key adalah student_id, value adalah kolom tambahan di tabel class_student
                $syncData[$id] = [
                    'academic_year_id' => $academicYearId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 3. Eksekusi Relasi Pivot
            // sync akan menyesuaikan anggota kelas sesuai pilihan terbaru
            $schoolClass->students()->sync($syncData);

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
        $schoolClass->fill([
            'homeroom_teacher_id' => $teacherId,
        ])->save();

        return $schoolClass->load('homeroomTeacher.user');
    }
}
