<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        $this->configureRateLimiting();

        // Slow query logger — redact bindings to prevent sensitive data leakage
        if (app()->environment('local')) {
            \DB::listen(function ($query) {
                if ($query->time > 100) {
                    // Redact bindings — replace values with type markers
                    $redactedBindings = array_map(
                        fn ($binding) => is_string($binding) ? '[redacted]' : $binding,
                        $query->bindings
                    );
                    \Log::warning('SLOW QUERY', [
                        'time_ms' => $query->time,
                        'sql' => $query->sql,
                        'bindings' => $redactedBindings,
                    ]);
                }
            });
        }
    }

    /**
     * Konfigurasi batas jumlah request API.
     */
    protected function configureRateLimiting(): void
    {
        // 1. Global API Limiter (120 req / menit per user)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // 2. Auth Limiter (10 req / menit untuk mencegah Brute Force)
        RateLimiter::for('auth-api', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // 3. Heavy Limiter (30 req / menit untuk Reports & Dashboard)
        RateLimiter::for('heavy-api', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // 4. Upload Limiter (60 req / menit)
        RateLimiter::for('upload-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
