<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // 1. Route Auth (Public & Semi-Protected)
            Route::middleware('api')
                ->prefix('api/v1/auth')
                ->group(base_path('routes/api/v1/auth.php'));

            // 2. Route Admin (Protected by Role: Admin)
            Route::middleware(['api', 'auth:api', 'role:admin'])
                ->prefix('api/v1/admin')
                ->group(base_path('routes/api/v1/admin.php'));

            // 3. Route Teacher (Protected by Role: Teacher)
            Route::middleware(['api', 'auth:api', 'role:teacher'])
                ->prefix('api/v1/teacher')
                ->group(base_path('routes/api/v1/teacher.php'));

            // 4. Route Student (Protected by Role: Student)
            Route::middleware(['api', 'auth:api', 'role:student'])
                ->prefix('api/v1/student')
                ->group(base_path('routes/api/v1/student.php'));

            Route::middleware(['api', 'auth:api', 'role:principal'])
                ->prefix('api/v1/principal')
                ->group(base_path('routes/api/v1/principal.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        });
    })->create();
