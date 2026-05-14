<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Rules\RecaptchaV2;

class AuthController 
{
    public function login(Request $request)
    {
        // 1. Validasi Input + reCAPTCHA
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'g-recaptcha-response' => ['required', new RecaptchaV2()] // <-- Panggil Rule di sini
        ], [
            'g-recaptcha-response.required' => 'Tanda centang reCAPTCHA wajib diisi.'
        ]);

        $credentials = $request->only('email', 'password');

        // 2. Coba Login via JWT
        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized. Email atau Password salah.'], 401);
        }

        // 3. Pengecekan Akun Aktif
        if (! Auth::guard('api')->user()->is_active) {
            Auth::guard('api')->logout();
            return response()->json(['error' => 'Akun dinonaktifkan. Hubungi Administrator.'], 403);
        }

        // 4. Sukses Login
        return $this->respondWithToken($token);
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
        return $this->respondWithToken($newToken);
    }

    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => Auth::guard('api')->user()
        ]);
    }
}