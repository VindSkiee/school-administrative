<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEskulRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eskulId = $this->route('eskul');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('eskuls', 'name')->ignore($eskulId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
