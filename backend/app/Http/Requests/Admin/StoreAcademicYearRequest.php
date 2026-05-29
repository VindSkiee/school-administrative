<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Gunakan regex untuk memastikan format wajib YYYY/YYYY (Contoh: 2025/2026)
            'name' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', Rule::in(['odd', 'even'])],
        ];
    }

    // Tambahkan pesan custom agar errornya mudah dimengerti oleh Frontend/User
    public function messages(): array
    {
        return [
            'name.regex' => 'Format tahun ajaran tidak valid. Harus menggunakan format YYYY/YYYY (contoh: 2025/2026).',
        ];
    }
}
