<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEskulRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:eskuls,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
