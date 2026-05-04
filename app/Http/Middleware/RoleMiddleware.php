<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * parameter string ...$roles memastikan semua role yang di-passing terbaca sebagai string array.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::guard('api')->user();

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'error' => 'Forbidden.',
                'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.'
            ], 403);
        }

        return $next($request);
    }
}