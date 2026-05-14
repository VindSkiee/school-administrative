<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaV2 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Bypass untuk environment testing atau local (opsional, berguna saat Anda run PHPUnit test)
        if (app()->environment('testing')) {
            return;
        }

        // Verifikasi token ke API Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $value,
            'remoteip' => request()->ip(), // Opsional: mengirim IP pengakses untuk keamanan ekstra
        ]);

        if (!$response->successful() || !$response->json('success')) {
            // Jika gagal, batalkan request dan kembalikan error validasi
            $fail('Validasi reCAPTCHA gagal. Pastikan Anda bukan robot.');
        }
    }
}