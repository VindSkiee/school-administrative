<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('api')->check() && Auth::guard('api')->user()?->must_change_password) {
            return response()->json([
                'error' => 'PASSWORD_CHANGE_REQUIRED',
                'message' => 'Anda diwajibkan untuk mengganti password default Anda.',
            ], 403);
        }

        return $next($request);
    }
}
