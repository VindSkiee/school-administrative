<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class GradeEskulRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grades' => ['required', 'array'],
            'grades.*.student_id' => ['required', 'exists:students,user_id'],
            'grades.*.eskul_id' => ['required', 'exists:eskuls,id'],
            'grades.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'grades.required' => 'Data penilaian wajib diisi.',
            'grades.*.student_id.required' => 'Siswa wajib dipilih.',
            'grades.*.student_id.exists' => 'Data siswa tidak valid.',
            'grades.*.eskul_id.required' => 'Ekstrakurikuler wajib dipilih.',
            'grades.*.eskul_id.exists' => 'Data eskul tidak valid.',
            'grades.*.score.numeric' => 'Nilai harus berupa angka.',
            'grades.*.score.min' => 'Nilai minimal adalah 0.',
            'grades.*.score.max' => 'Nilai maksimal adalah 100.',
        ];
    }
}
