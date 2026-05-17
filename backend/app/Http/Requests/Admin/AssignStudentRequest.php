<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignStudentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // student_ids harus berupa array dan tidak boleh kosong
            'student_ids' => ['required', 'array', 'min:1'],
            // memastikan setiap ID benar-benar ada di tabel students
            'student_ids.*' => ['required', 'exists:students,user_id'],
        ];
    }
}