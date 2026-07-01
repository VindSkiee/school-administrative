<?php

namespace App\Http\Controllers\API;

use App\Models\AcademicYear;
use App\Models\User;
use App\Rules\RecaptchaV2;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function login(Request $request): JsonResponse
    {
        // 1. Validasi Input Dasar — 'email' field menerima email, NIP, NIS, atau NISN
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $credential = $request->input('email');

        // 2. Cari User berdasarkan email, NISN, NIS, atau NIP (silang ke semua tabel profile)
        $user = $this->resolveUserFromCredential($credential);

        // Verifikasi keberadaan user dan kecocokan password secara manual (karena Sanctum API)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        // 3. THE GATEKEEPER: Cek reCAPTCHA HANYA JIKA role adalah admin atau kepala sekolah
        if ($user->role === 'admin' || $user->role === 'principal') {
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
        $user->tokens()->delete();

        $remember = $request->boolean('remember', false);
        $expiresAt = $remember ? Carbon::now()->addDays(30) : Carbon::now()->addHours(24);

        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        // 5. Sukses Login
        return $this->respondWithToken($token, $user, $expiresAt);
    }

    /**
     * Resolve user dari credential (email, NISN, NIS, atau NIP).
     */
    private function resolveUserFromCredential(string $credential): ?User
    {
        // Coba cari berdasarkan email dulu (paling cepat, ada index)
        $user = User::where('email', $credential)->where('is_active', true)->first();
        if ($user) {
            return $user;
        }

        // Cari berdasarkan NISN (tabel students)
        $user = User::whereHas('student', fn ($q) => $q->where('nisn', $credential))
            ->where('is_active', true)->first();
        if ($user) {
            return $user;
        }

        // Cari berdasarkan NIS (tabel students)
        $user = User::whereHas('student', fn ($q) => $q->where('nis', $credential))
            ->where('is_active', true)->first();
        if ($user) {
            return $user;
        }

        // Cari berdasarkan NIP (tabel teachers, admins, principals)
        $user = User::where(fn ($q) => $q->whereHas('teacher', fn ($t) => $t->where('nip', $credential))
            ->orWhereHas('admin', fn ($a) => $a->where('nip', $credential))
            ->orWhereHas('principal', fn ($p) => $p->where('nip', $credential)))
            ->where('is_active', true)->first();

        return $user;
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
                ->filter(fn ($item) => $item['academic_year_id'] !== null)
                ->unique('academic_year_id')
                ->values()
                ->toArray();

            $userData['grade_history'] = $gradeHistory;
        }

        return $userData;
    }

    protected function respondWithToken(string $token, User $user, Carbon $expiresAt): JsonResponse
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
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
            'email' => 'required|string',
        ]);

        $user = $this->resolveUserFromCredential($request->input('email'));

        $requiresCaptcha = $user && in_array($user->role, ['admin', 'principal']);

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
