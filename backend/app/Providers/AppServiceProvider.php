<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Konfigurasi batas jumlah request API.
     */
    protected function configureRateLimiting(): void
    {
        // 1. Global API Limiter (Standar operasional: 60 req / menit)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 2. Auth Limiter (Sangat ketat: 5 req / menit untuk mencegah Brute Force)
        RateLimiter::for('auth-api', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // 3. Heavy Limiter (Ketat: 15 req / menit untuk Reports & Dashboard)
        RateLimiter::for('heavy-api', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        // 4. Upload Limiter (Mencegah spam storage: 30 req / menit)
        RateLimiter::for('upload-api', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}