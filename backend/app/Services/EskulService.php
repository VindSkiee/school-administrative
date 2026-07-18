<?php

namespace App\Services;

use App\Models\Eskul;
use App\Models\StudentEskul;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EskulService
{
    public function getAll(): array
    {
        return Cache::remember('admin_eskuls_list', 120, function () {
            return Eskul::with('teacher:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Eskul $eskul) => [
                    'id' => $eskul->id,
                    'name' => $eskul->name,
                    'description' => $eskul->description,
                    'teacher_id' => $eskul->teacher_id,
                    'teacher_name' => $eskul->teacher?->name ?? '-',
                    'is_active' => $eskul->is_active,
                    'student_count' => $eskul->studentEskuls()->count(),
                    'created_at' => $eskul->created_at,
                    'updated_at' => $eskul->updated_at,
                ])
                ->toArray();
        });
    }

    public function getById(int $id): array
    {
        $eskul = Eskul::with(['teacher:id,name', 'studentEskul' => function ($query) {
            $query->with('student:id,name')
                ->join('users', 'users.id', '=', 'student_eskuls.student_id');
        }])->findOrFail($id);

        return [
            'id' => $eskul->id,
            'name' => $eskul->name,
            'description' => $eskul->description,
            'teacher_id' => $eskul->teacher_id,
            'teacher_name' => $eskul->teacher?->name ?? '-',
            'is_active' => $eskul->is_active,
            'student_count' => $eskul->studentEskuls()->count(),
            'students' => $eskul->studentEskul->map(fn ($se) => [
                'student_id' => $se->student_id,
                'student_name' => $se->student?->user?->name ?? '-',
                'academic_year_id' => $se->academic_year_id,
                'score' => $se->score,
                'description' => $se->description,
            ]),
            'created_at' => $eskul->created_at,
            'updated_at' => $eskul->updated_at,
        ];
    }

    public function create(array $data): Eskul
    {
        $eskul = Eskul::create($data);

        $this->invalidateCache();

        return $eskul->load('teacher:id,name');
    }

    public function update(int $id, array $data): Eskul
    {
        $eskul = Eskul::findOrFail($id);
        $eskul->update($data);

        $this->invalidateCache();

        return $eskul->load('teacher:id,name');
    }

    public function delete(int $id): void
    {
        $eskul = Eskul::findOrFail($id);

        if ($eskul->studentEskuls()->exists()) {
            throw new HttpException(422, 'Tidak dapat menghapus eskul karena masih ada siswa yang terdaftar.');
        }

        $eskul->delete();

        $this->invalidateCache();
    }

    public function assignTeacher(int $eskulId, ?int $teacherId): Eskul
    {
        $eskul = Eskul::findOrFail($eskulId);

        if ($teacherId !== null) {
            $teacher = User::where('id', $teacherId)->where('role', 'teacher')->first();
            if (! $teacher) {
                throw new HttpException(422, 'Guru yang dipilih tidak valid.');
            }
        }

        $eskul->update(['teacher_id' => $teacherId]);

        $this->invalidateCache();

        return $eskul->load('teacher:id,name');
    }

    public function getActiveOptions(): array
    {
        return Eskul::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Eskul $eskul) => [
                'id' => $eskul->id,
                'name' => $eskul->name,
                'description' => $eskul->description,
            ])
            ->toArray();
    }

    public function getEskulReadiness(int $academicYearId): array
    {
        $studentsWithEskul = StudentEskul::where('academic_year_id', $academicYearId)
            ->whereNull('score')
            ->count();

        $totalStudentsWithEskul = StudentEskul::where('academic_year_id', $academicYearId)
            ->count();

        return [
            'total_enrolled' => $totalStudentsWithEskul,
            'ungraded_count' => $studentsWithEskul,
            'is_ready' => $studentsWithEskul === 0,
        ];
    }

    private function invalidateCache(): void
    {
        Cache::forget('admin_eskuls_list');
    }
}
