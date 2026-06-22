<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\AcademicYear;
use App\Rules\RecaptchaV2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function login(Request $request): JsonResponse
    {
        // 1. Validasi Input Dasar
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // 2. Cari User
        $user = User::query()->where('email', $request->email)->where('is_active', true)->first();

        // Verifikasi keberadaan user dan kecocokan password secara manual (karena Sanctum API)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Gunakan ValidationException agar response error otomatis diseragamkan oleh Laravel
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        // 3. THE GATEKEEPER: Cek reCAPTCHA HANYA JIKA role adalah admin
        if ($user->role === 'admin') {
            if (! $request->filled('g-recaptcha-response')) {
                return response()->json([
                    'error' => 'Verifikasi keamanan reCAPTCHA wajib untuk Admin.',
                    'recaptcha_required' => true,
                ], 428);
            }

            $request->validate([
                'g-recaptcha-response' => ['required', 'string', new RecaptchaV2],
            ], [
                'g-recaptcha-response.required' => 'Verifikasi keamanan reCAPTCHA wajib untuk Admin.',
            ]);
        }

        // 4. Generate Sanctum Token
        // Hapus semua token lama agar satu akun hanya aktif di satu perangkat (opsional, hapus baris ini jika ingin multi-device)
        $user->tokens()->delete(); 

        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Sukses Login
        return $this->respondWithToken($token, $user);
    }

    private function formatUserData(User $user): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $user->loadMissing([
            'student.classes.academicYear',
            'admin',
            'principal',
            'teacher', // Pastikan teacher di-load agar relasi di bawahnya aman
        ]);

        if ($user->teacher && ! $user->teacher->relationLoaded('schedules')) {
            $schedulesQuery = $user->teacher->schedules();
            if ($activeYear) {
                $schedulesQuery->where('academic_year_id', $activeYear->id);
            }
            $user->teacher->setRelation('schedules', $schedulesQuery->with(['subject', 'schoolClass'])->get());
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url,
            'must_change_password' => $user->must_change_password,
            'is_active' => $user->is_active,
            
            // Flat Data Identitas
            'nip' => $user->teacher?->nip ?? $user->admin?->nip ?? $user->principal?->nip,
            'nis' => $user->student?->nis,
            'nisn' => $user->student?->nisn,

            // Nested Data Profil
            'student' => $user->student,
            'teacher' => $user->teacher,
            'admin' => $user->admin,
            'principal' => $user->principal,
        ];

        // Format 'grade_history' khusus untuk Role Siswa
        if ($user->role === 'student' && $user->student) {
            $gradeHistory = $user->student->classes->map(function ($schoolClass) {
                return [
                    'class_name' => $schoolClass->name,
                    'academic_year_id' => $schoolClass->academicYear?->id,
                    'academic_year_name' => $schoolClass->academicYear?->name,
                    'semester' => $schoolClass->academicYear?->semester,
                ];
            })
            ->filter(fn($item) => $item['academic_year_id'] !== null)
            ->unique('academic_year_id')
            ->values()
            ->toArray();

            $userData['grade_history'] = $gradeHistory;
        }

        return $userData;
    }

    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            // Sanctum tidak punya JWTAuth::factory()->getTTL(), token bertahan sesuai config/sanctum.php (expiration)
            'user' => $this->formatUserData($user), 
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        // Menggunakan $request->user() adalah cara standar Sanctum mengambil data user saat ini
        $user = $request->user();

        return response()->json(
            $this->formatUserData($user)
        )->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Hapus token yang sedang digunakan untuk request ini
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    // Fungsi refresh() DIHAPUS. 
    // Sanctum tidak memiliki mekanisme refresh token bawaan seperti JWT. 
    // Token valid sampai kedaluwarsa sesuai config/sanctum.php (biasanya bertahun-tahun jika tidak diatur).
    // Jika token habis, user harus login ulang.

    public function checkRequirements(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()->where('email', $request->email)->first();

        $requiresCaptcha = $user && $user->role === 'admin';

        return response()->json([
            'requires_captcha' => $requiresCaptcha,
        ]);
    }

    public function forceChangePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai.',
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan lanjutkan.',
        ]);
    }
}