<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $keys = array_keys($this->input());
        $teacherOnlyUpdate = $isUpdate && $keys === ['teacher_id'];

        if ($teacherOnlyUpdate) {
            return [
                'teacher_id' => ['required', 'exists:teachers,user_id'],
            ];
        }

        $baseRules = [
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,user_id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];

        if ($isUpdate) {
            return array_merge($baseRules, [
                'days' => ['nullable', 'array', 'min:1'],
                'days.*' => ['required_with:days', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])],
                'day_of_week' => ['nullable', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])],
            ]);
        }

        return array_merge($baseRules, [
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required_with:days', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])],
        ]);
    }

    public function messages(): array
    {
        return [
            'days.required' => 'Pilih minimal satu hari untuk jadwal.',
            'days.min' => 'Pilih minimal satu hari untuk jadwal.',
            'days.*.in' => 'Hari tidak valid.',
            'end_time.after' => 'Jam selesai harus lebih lambat dari jam mulai.',
        ];
    }
}
