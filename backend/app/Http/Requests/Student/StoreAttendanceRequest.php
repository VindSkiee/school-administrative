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
            function (Validator $validator): void {
                $scheduleId = $this->input('schedule_id');

                if (! $scheduleId) {
                    return;
                }

                $schedule = Schedule::query()->find($scheduleId);
                if (! $schedule) {
                    return;
                }

                $user = $this->user('api');
                if (! $user) {
                    return;
                }

                $student = Student::query()->find($user->id);
                if (! $student || ! $student->class_id) {
                    $validator->errors()->add('schedule_id', 'Siswa belum terdaftar pada kelas.');

                    return;
                }

                if ((int) $schedule->class_id !== (int) $student->class_id) {
                    $validator->errors()->add('schedule_id', 'Jadwal tidak sesuai dengan kelas siswa.');
                }
            },
        ];
    }
}
