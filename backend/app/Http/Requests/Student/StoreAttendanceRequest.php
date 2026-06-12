<?php

namespace App\Http\Requests\Student;

use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'type' => ['required', 'in:sick,permission'],
            'reason' => ['required', 'string', 'max:500'],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // Maks 2MB
        ];
    }

    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator): void {
                $scheduleId = $this->input('schedule_id');

                if (! $scheduleId) {
                    return;
                }

                $schedule = \App\Models\Schedule::query()->find($scheduleId);
                if (! $schedule) {
                    return;
                }

                $user = $this->user('api');
                if (! $user) {
                    return;
                }

                // PERBAIKAN: Ambil student beserta relasi kelasnya
                $student = \App\Models\Student::with('classes')->find($user->id);
                $activeClass = $student ? $student->classes->first() : null;

                // PERBAIKAN: Gunakan $activeClass
                if (! $activeClass) {
                    $validator->errors()->add('schedule_id', 'Siswa belum terdaftar pada kelas aktif.');
                    return;
                }

                // PERBAIKAN: Bandingkan dengan id kelas aktif
                if ((int) $schedule->class_id !== (int) $activeClass->id) {
                    $validator->errors()->add('schedule_id', 'Jadwal tidak sesuai dengan kelas siswa.');
                }
            },
        ];
    }
}
