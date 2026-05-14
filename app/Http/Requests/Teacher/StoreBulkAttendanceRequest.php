<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBulkAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Otorisasi detail dilakukan di Service

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'attendances' => ['required', 'array', 'min:1'], // Array data kehadiran siswa
            'attendances.*.student_id' => ['required', 'exists:students,user_id'],
            'attendances.*.status' => ['required', Rule::in(['present', 'alpa', 'sick', 'permission', 'late'])],
        ];
    }
}