<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Eskul;
use App\Models\StudentEskul;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeacherEskulService
{
    public function isTeacherPIC(int $teacherId): bool
    {
        return Eskul::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->exists();
    }

    public function getAssignedEskuls(int $teacherId): array
    {
        return Eskul::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Eskul $eskul) => [
                'id' => $eskul->id,
                'name' => $eskul->name,
                'description' => $eskul->description,
            ])
            ->toArray();
    }

    public function getStudentsByEskul(int $teacherId, ?int $eskulId = null, ?int $classId = null): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return [];
        }

        $eskulIds = $eskulId
            ? [$eskulId]
            : Eskul::where('teacher_id', $teacherId)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

        if (empty($eskulIds)) {
            return [];
        }

        $query = StudentEskul::whereIn('eskul_id', $eskulIds)
            ->where('student_eskuls.academic_year_id', $activeYear->id)
            ->join('eskuls', 'eskuls.id', '=', 'student_eskuls.eskul_id')
            ->join('users', 'users.id', '=', 'student_eskuls.student_id')
            ->join('students', 'students.user_id', '=', 'student_eskuls.student_id')
            ->leftJoin('class_student', function ($join) use ($activeYear) {
                $join->on('class_student.student_id', '=', 'student_eskuls.student_id')
                    ->where('class_student.academic_year_id', '=', $activeYear->id);
            })
            ->leftJoin('classes', 'classes.id', '=', 'class_student.class_id')
            ->select(
                'student_eskuls.*',
                'users.name as student_name',
                'classes.name as class_name',
                'classes.id as class_id',
                'eskuls.name as eskul_name'
            );

        if ($classId) {
            $query->where('class_student.class_id', $classId);
        }

        $results = $query->orderBy('classes.name')
            ->orderBy('users.name')
            ->get();

        $grouped = $results->groupBy('class_name');

        return $grouped->map(function ($students, $className) {
            return [
                'class_name' => $className ?: 'Tanpa Kelas',
                'students' => $students->map(fn ($s) => [
                    'student_eskul_id' => $s->id,
                    'student_id' => $s->student_id,
                    'student_name' => $s->student_name,
                    'eskul_id' => $s->eskul_id,
                    'eskul_name' => $s->eskul_name ?? '-',
                    'score' => $s->score,
                    'description' => $s->description,
                    'graded_at' => $s->graded_at?->toIso8601String(),
                ])->toArray(),
            ];
        })->values()->toArray();
    }

    public function gradeStudents(int $teacherId, array $grades): int
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new HttpException(422, 'Tidak ada tahun ajaran aktif.');
        }

        $assignedEskulIds = Eskul::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $gradedCount = 0;

        DB::transaction(function () use ($grades, $teacherId, $assignedEskulIds, $activeYear, &$gradedCount) {
            foreach ($grades as $grade) {
                if (! in_array($grade['eskul_id'], $assignedEskulIds, true)) {
                    throw new HttpException(403, 'Anda tidak memiliki akses untuk menilai eskul ini.');
                }

                $studentEskul = StudentEskul::where('student_id', $grade['student_id'])
                    ->where('eskul_id', $grade['eskul_id'])
                    ->where('academic_year_id', $activeYear->id)
                    ->first();

                if (! $studentEskul) {
                    throw new HttpException(404, 'Siswa tidak terdaftar di eskul ini.');
                }

                $studentEskul->update([
                    'score' => $grade['score'] ?? null,
                    'description' => $grade['description'] ?? null,
                    'graded_at' => now(),
                    'graded_by' => $teacherId,
                ]);

                $gradedCount++;
            }
        });

        return $gradedCount;
    }
}
