<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Rules\RecaptchaV2;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController
{
    public function login(Request $request): JsonResponse
    {
        // 1. Validasi Input Dasar
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // 2. Cari User untuk mengetahui Role-nya
        $user = User::query()->where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['error' => 'Kredensial tidak valid.'], 401);
        }

        $credentials = $request->only('email', 'password');
        $credentials['is_active'] = true;

        // 3. THE GATEKEEPER: Cek reCAPTCHA HANYA JIKA role adalah admin
        if ($user->role === 'admin') {
            if (! Auth::guard('api')->validate($credentials)) {
                return response()->json(['error' => 'Kredensial tidak valid.'], 401);
            }

            if (! $request->filled('g-recaptcha-response')) {
                return response()->json([
                    'error' => 'Verifikasi keamanan reCAPTCHA wajib untuk Admin.',
                    'recaptcha_required' => true,
                ], 428);
            }

            // Kita gunakan kembali Custom Rule RecaptchaV2 di sini! Jauh lebih bersih.
            $request->validate([
                'g-recaptcha-response' => ['required', 'string', new RecaptchaV2],
            ], [
                'g-recaptcha-response.required' => 'Verifikasi keamanan reCAPTCHA wajib untuk Admin.',
            ]);

            $token = Auth::guard('api')->attempt($credentials);
        } else {
            $token = Auth::guard('api')->attempt($credentials);
        }

        // 4. Verifikasi Password & Generate JWT Token
        if (! $token) {
            return response()->json(['error' => 'Kredensial tidak valid.'], 401);
        }

        // 5. Sukses Login
        return $this->respondWithToken($token, $user);
    }

    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'must_change_password' => $user->must_change_password,
            ],
        ]);
    }

    public function me()
    {
        $user = Auth::guard('api')->user();

        if ($user instanceof Model) {
            $user->load(['student', 'teacher']);
        }

        return response()->json($user);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function refresh()
    {
        $newToken = JWTAuth::refresh();
        /** @var User $user */
        $user = Auth::guard('api')->user();

        return $this->respondWithToken($newToken, $user);
    }

    public function checkRequirements(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()->where('email', $request->email)->first();

        // Hanya kembalikan TRUE jika user ada DAN dia adalah admin.
        // Jika user tidak ada atau bukan admin, kembalikan FALSE.
        // Ini mencegah celah Email Enumeration.
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
        $user = Auth::guard('api')->user();

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
