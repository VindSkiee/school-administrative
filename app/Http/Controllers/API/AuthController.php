<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Rules\RecaptchaV2;

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

        if (!$user) {
            return response()->json(['error' => 'Kredensial tidak valid.'], 401);
        }

        // 3. THE GATEKEEPER: Cek reCAPTCHA HANYA JIKA role adalah admin
        if ($user->role === 'admin') {
            // Kita gunakan kembali Custom Rule RecaptchaV2 di sini! Jauh lebih bersih.
            $request->validate([
                'g-recaptcha-response' => ['required', 'string', new RecaptchaV2()]
            ], [
                'g-recaptcha-response.required' => 'Verifikasi keamanan reCAPTCHA wajib untuk Admin.'
            ]);
        }

        // 4. Verifikasi Password & Generate JWT Token
        $credentials = $request->only('email', 'password');
        
        if (!$token = Auth::guard('api')->attempt($credentials)) {
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
                'role' => $user->role
            ]
        ]);
    }

    public function me()
    {
        $user = Auth::guard('api')->user();
        
        if ($user instanceof \Illuminate\Database\Eloquent\Model) {
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
}