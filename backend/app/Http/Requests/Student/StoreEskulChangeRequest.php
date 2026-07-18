<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreEskulChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eskul_id' => ['required', 'exists:eskuls,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'eskul_id.required' => 'Pilih ekstrakurikuler tujuan.',
            'eskul_id.exists' => 'Ekstrakurikuler yang dipilih tidak valid.',
        ];
    }
}
