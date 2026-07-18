<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreEskulSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eskul_ids' => ['required', 'array'],
            'eskul_ids.*' => ['exists:eskuls,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'eskul_ids.required' => 'Pilih minimal satu ekstrakurikuler.',
            'eskul_ids.array' => 'Format data eskul tidak valid.',
            'eskul_ids.*.exists' => 'Ekstrakurikuler yang dipilih tidak valid.',
        ];
    }
}
