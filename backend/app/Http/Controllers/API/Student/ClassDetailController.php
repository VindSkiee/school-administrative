<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClassDetailController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        
        /** @var \App\Models\User $user */
        $student = $user->student()->with(['classes' => function($q) {
            $q->latest(); // Ambil kelas yang paling baru
        }])->first();

        $activeClass = $student->classes->first();

        if (!$activeClass) {
            return response()->json(['error' => 'Anda belum terdaftar di kelas manapun.'], 404);
        }

        // Load relasi yang dibutuhkan: Wali Kelas & Semua Siswa (beserta User/Avatar-nya)
        $activeClass->load([
            'homeroomTeacher.user',
            'students.user',
            'academicYear'
        ]);

        // Mapping (Format ulang) data siswa agar mudah dibaca oleh BaseTable di Frontend
        $students = $activeClass->students->map(function ($s) {
            return [
                'id' => $s->user_id,
                'name' => $s->user->name ?? 'Tanpa Nama',
                'nis' => $s->nis ?? '-',
                'nisn' => $s->nisn ?? '-',
                'gender' => $s->gender === 'L' ? 'Laki-laki' : ($s->gender === 'P' ? 'Perempuan' : '-'),
                'avatar_url' => $s->user->avatar_url ?? null,
                'status' => $s->status
            ];
        })->sortBy('name')->values(); // Urutkan berdasarkan Abjad

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $activeClass->id,
                'name' => $activeClass->name,
                'academic_year' => $activeClass->academicYear->name ?? '-',
                'homeroom_teacher' => [
                    'id' => $activeClass->homeroomTeacher->user_id ?? null,
                    'name' => $activeClass->homeroomTeacher->user->name ?? 'Belum Ditentukan',
                    'nip' => $activeClass->homeroomTeacher->nip ?? '-',
                    'avatar_url' => $activeClass->homeroomTeacher->user->avatar_url ?? null,
                ],
                'students' => $students
            ]
        ]);
    }
}