<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TeacherStudentController extends Controller
{
    public function showProfile(string $id): JsonResponse
    {
        // PERBAIKAN: Gunakan whereHas('student') alih-alih where('role', 'student')
        $user = User::with(['student.classes.academicYear'])
            ->whereHas('student') // <--- UBAH BARIS INI
            ->findOrFail($id);

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url,
            'is_active' => $user->is_active,
            'nis' => $user->student?->nis,
            'nisn' => $user->student?->nisn,
            'student' => $user->student,
        ];

        return response()->json($userData);
    }
}