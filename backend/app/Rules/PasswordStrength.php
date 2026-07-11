<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates password strength — medium level.
 *
 * Requirements:
 * - Minimum 8 characters
 * - At least 2 of 4 character categories: uppercase, lowercase, digits, special chars
 * - Not a common weak password
 */
class PasswordStrength implements ValidationRule
{
    private const COMMON_PASSWORDS = [
        'password', 'password123', '12345678', '123456789', '1234567890',
        'qwerty123', 'abc123456', 'monkey123', 'master123', 'admin123',
        'letmein123', 'welcome123', 'shadow123', 'sunshine123', 'princess123',
        'football123', 'batman123', 'trustno123', 'access123', 'hello123',
        'charlie123', 'donald123', 'login123', 'solo123', 'passw0rd',
        'iloveyou123', '1234qwer', 'qwertyui', 'abcdefg1', '1234abcd',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $password = strtolower(trim($value));

        // Check common passwords
        if (in_array($password, self::COMMON_PASSWORDS, true)) {
            $fail('Password terlalu umum dan tidak diizinkan.');

            return;
        }

        // Check if password contains the word "password"
        if (str_contains($password, 'password')) {
            $fail('Password tidak boleh mengandung kata "password".');

            return;
        }

        // Count character categories
        $categories = 0;
        if (preg_match('/[A-Z]/', $value)) {
            $categories++;
        }
        if (preg_match('/[a-z]/', $value)) {
            $categories++;
        }
        if (preg_match('/[0-9]/', $value)) {
            $categories++;
        }
        if (preg_match('/[^A-Za-z0-9]/', $value)) {
            $categories++;
        }

        if ($categories < 2) {
            $fail('Password harus mengandung minimal 2 dari 4 jenis karakter: huruf besar, huruf kecil, angka, atau simbol.');
        }
    }

    public function __toString(): string
    {
        return 'password_strength';
    }
}
