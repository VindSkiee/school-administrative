<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController 
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' =>'Email atau Password salah.'], 401);
        }

        if (! Auth::guard('api')->user()->is_active) {
            Auth::guard('api')->logout();
            return response()->json(['error' => 'Akun dinonaktifkan. Hubungi Administrator.'], 403);
        }

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