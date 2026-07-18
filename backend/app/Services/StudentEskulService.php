<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Eskul;
use App\Models\EskulChangeRequest;
use App\Models\Student;
use App\Models\StudentEskul;
use Carbon\Carbon;
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

        $deadline = $this->resolveDeadline($activeYear);
        if ($deadline && Carbon::today()->gt($deadline)) {
            throw new HttpException(422, 'Batas waktu pendaftaran eskul telah habis.');
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

        $hasCurrentEskul = StudentEskul::where('student_id', $studentId)
            ->where('academic_year_id', $activeYear->id)
            ->exists();

        if ($hasCurrentEskul) {
            throw new HttpException(422, 'Anda sudah terdaftar di ekstrakurikuler. Gunakan fitur pergantian eskul untuk mengubah pilihan.');
        }

        DB::transaction(function () use ($studentId, $eskulIds, $activeYear) {
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

    public function skipSelection(int $studentId): void
    {
        $student = Student::where('user_id', $studentId)->first();
        if (! $student) {
            throw new HttpException(404, 'Data siswa tidak ditemukan.');
        }

        Student::where('user_id', $studentId)
            ->update(['eskul_selection_completed' => true]);
    }

    public function getMyEskuls(int $studentId): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return [
                'current_eskuls' => [],
                'has_current_eskul' => false,
                'has_pending_change_request' => false,
                'pending_change_request' => null,
            ];
        }

        $currentEskuls = StudentEskul::where('student_id', $studentId)
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

        $pendingRequest = EskulChangeRequest::where('student_id', $studentId)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', 'pending')
            ->with(['currentEskul:id,name', 'requestedEskul:id,name'])
            ->first();

        $hasPending = $pendingRequest !== null;

        $pendingData = null;
        if ($pendingRequest) {
            $pendingData = [
                'id' => $pendingRequest->id,
                'current_eskul_name' => $pendingRequest->currentEskul?->name ?? '-',
                'requested_eskul_name' => $pendingRequest->requestedEskul?->name ?? '-',
                'created_at' => $pendingRequest->created_at?->toIso8601String(),
            ];
        }

        return [
            'current_eskuls' => $currentEskuls,
            'has_current_eskul' => count($currentEskuls) > 0,
            'has_pending_change_request' => $hasPending,
            'pending_change_request' => $pendingData,
        ];
    }

    public function getDeadline(): ?array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return null;
        }

        $deadline = $this->resolveDeadline($activeYear);

        return [
            'deadline' => $deadline?->format('Y-m-d'),
            'is_passed' => $deadline ? Carbon::today()->gt($deadline) : false,
        ];
    }

    private function resolveDeadline(AcademicYear $activeYear): ?Carbon
    {
        $deadline = $activeYear->eskul_registration_deadline;
        if (! $deadline && $activeYear->start_date) {
            $deadline = Carbon::parse($activeYear->start_date)->addDays(14);
        }

        return $deadline ? Carbon::parse($deadline) : null;
    }

    public function submitChangeRequest(int $studentId, int $newEskulId): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new HttpException(422, 'Tidak ada tahun ajaran aktif.');
        }

        $currentEskul = StudentEskul::where('student_id', $studentId)
            ->where('academic_year_id', $activeYear->id)
            ->first();

        if (! $currentEskul) {
            throw new HttpException(422, 'Anda belum terdaftar di ekstrakurikuler manapun. Silakan daftar terlebih dahulu.');
        }

        if ($currentEskul->eskul_id === $newEskulId) {
            throw new HttpException(422, 'Eskul baru harus berbeda dari eskul saat ini.');
        }

        $newEskul = Eskul::where('id', $newEskulId)->where('is_active', true)->first();
        if (! $newEskul) {
            throw new HttpException(422, 'Ekstrakurikuler yang dipilih tidak valid atau tidak aktif.');
        }

        $pendingExists = EskulChangeRequest::where('student_id', $studentId)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            throw new HttpException(422, 'Anda sudah memiliki pengajuan pergantian eskul yang belum diproses.');
        }

        EskulChangeRequest::create([
            'student_id' => $studentId,
            'current_eskul_id' => $currentEskul->eskul_id,
            'requested_eskul_id' => $newEskulId,
            'academic_year_id' => $activeYear->id,
            'status' => 'pending',
        ]);
    }

    public function cancelChangeRequest(int $studentId): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            throw new HttpException(422, 'Tidak ada tahun ajaran aktif.');
        }

        $pendingRequest = EskulChangeRequest::where('student_id', $studentId)
            ->where('academic_year_id', $activeYear->id)
            ->where('status', 'pending')
            ->first();

        if (! $pendingRequest) {
            throw new HttpException(404, 'Tidak ada pengajuan pergantian eskul yang sedang menunggu.');
        }

        $pendingRequest->delete();
    }
}
