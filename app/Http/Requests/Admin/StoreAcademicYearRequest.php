<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dihandle oleh middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'example: 2025/2026'],
            'semester' => ['required', Rule::in(['odd', 'even'])],
        ];
    }
}