<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGradingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'task_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'uts_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'uas_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'attendance_weight' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $taskWeight = (int) $this->input('task_weight', 0);
            $utsWeight = (int) $this->input('uts_weight', 0);
            $uasWeight = (int) $this->input('uas_weight', 0);
            $attendanceWeight = (int) $this->input('attendance_weight', 0);

            $total = $taskWeight + $utsWeight + $uasWeight + $attendanceWeight;

            if ($total !== 100) {
                $validator->errors()->add(
                    'weights',
                    "Total bobot keseluruhan (Tugas, UTS, UAS, dan Kehadiran) harus tepat 100%. Saat ini totalnya {$total}%."
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function messages(): array
    {
        return [
            'academic_year_id.exists' => 'Tahun ajaran yang dipilih tidak ditemukan.',
            'task_weight.required' => 'Bobot tugas wajib diisi.',
            'uts_weight.required' => 'Bobot UTS wajib diisi.',
            'uas_weight.required' => 'Bobot UAS wajib diisi.',
            'attendance_weight.required' => 'Bobot kehadiran wajib diisi.',
        ];
    }
}
