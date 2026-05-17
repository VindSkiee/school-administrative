<?php

namespace App\Http\Requests\Teacher;

use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    } // Otorisasi detail dilakukan di Service

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

                $studentIds = collect($this->input('attendances', []))
                    ->pluck('student_id')
                    ->filter()
                    ->unique()
                    ->values();

                if ($studentIds->isEmpty()) {
                    return;
                }

                $validStudentIds = Student::query()
                    ->whereIn('user_id', $studentIds->all(), 'and', false)
                    ->where('class_id', $schedule->class_id)
                    ->pluck('user_id')
                    ->all();

                $invalidStudentIds = $studentIds->diff($validStudentIds);

                if ($invalidStudentIds->isNotEmpty()) {
                    $validator->errors()->add('attendances', 'Beberapa siswa tidak terdaftar di kelas pada jadwal ini.');
                }
            },
        ];
    }
}
