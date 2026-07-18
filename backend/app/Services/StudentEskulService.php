<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Eskul;
use App\Models\Student;
use App\Models\StudentEskul;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudentEskulService
{
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

    public function submitSelection(int $studentId, array $eskulIds): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new HttpException(422, 'Tidak ada tahun ajaran aktif.');
        }

        $student = Student::where('user_id', $studentId)->first();
        if (! $student) {
            throw new HttpException(404, 'Data siswa tidak ditemukan.');
        }

        $isInActiveClass = DB::table('class_student')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $activeYear->id)
            ->exists();

        if (! $isInActiveClass) {
            throw new HttpException(422, 'Anda belum terdaftar di kelas mana pun pada semester aktif.');
        }

        DB::transaction(function () use ($studentId, $eskulIds, $activeYear) {
            StudentEskul::where('student_id', $studentId)
                ->where('academic_year_id', $activeYear->id)
                ->delete();

            foreach ($eskulIds as $eskulId) {
                StudentEskul::create([
                    'student_id' => $studentId,
                    'eskul_id' => $eskulId,
                    'academic_year_id' => $activeYear->id,
                ]);
            }

            Student::where('user_id', $studentId)
                ->update(['eskul_selection_completed' => true]);
        });
    }

    public function getMyEskuls(int $studentId): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return [];
        }

        return StudentEskul::where('student_id', $studentId)
            ->where('academic_year_id', $activeYear->id)
            ->with(['eskul:id,name,description', 'gradedBy:id,name'])
            ->get()
            ->map(fn (StudentEskul $se) => [
                'id' => $se->id,
                'eskul_id' => $se->eskul_id,
                'eskul_name' => $se->eskul?->name ?? '-',
                'eskul_description' => $se->eskul?->description ?? '-',
                'score' => $se->score,
                'description' => $se->description,
                'graded_at' => $se->graded_at?->toIso8601String(),
                'graded_by_name' => $se->gradedBy?->name ?? '-',
            ])
            ->toArray();
    }

    public function isSelectionCompleted(int $studentId): bool
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return true;
        }

        $student = Student::where('user_id', $studentId)->first();
        if (! $student) {
            return true;
        }

        return (bool) $student->eskul_selection_completed;
    }

    public function resetSelectionsForNewSemester(): int
    {
        return Student::where('eskul_selection_completed', true)
            ->update(['eskul_selection_completed' => false]);
    }
}
