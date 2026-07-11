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
            'name' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', Rule::in(['odd', 'even'])],
            'phase' => ['required', 'string', 'max:1', 'regex:/^[A-F]$/'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'gte:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Format tahun ajaran tidak valid. Harus menggunakan format YYYY/YYYY (contoh: 2025/2026).',
            'phase.regex' => 'Fase harus berupa satu huruf kapital A-F (contoh: D).',
            'end_date.gte' => 'Tanggal berakhir harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
