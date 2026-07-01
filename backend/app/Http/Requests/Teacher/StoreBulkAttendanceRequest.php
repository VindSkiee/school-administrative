<?php

namespace App\Http\Requests\Teacher;

use App\Models\MeetingSession;
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
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'exists:students,user_id'],
            'attendances.*.status' => ['required', Rule::in(['present', 'absent', 'sick', 'permission', 'late'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $scheduleId = $this->input('schedule_id');
                $date = $this->input('date');

                if (! $scheduleId || ! $date) {
                    return;
                }

                $meetingSession = MeetingSession::query()
                    ->where('schedule_id', $scheduleId)
                    ->where('date', $date)
                    ->first();

                if (! $meetingSession) {
                    $validator->errors()->add('date', 'Tidak ada sesi pertemuan untuk tanggal ini.');

                    return;
                }

                if ($meetingSession->status === 'holiday') {
                    $validator->errors()->add('date', 'Tanggal ini adalah hari libur. Absensi tidak dapat diisi.');

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
                    ->whereIn('user_id', $studentIds->all())
                    ->whereHas('classes', function ($query) use ($schedule) {
                        $query->where('classes.id', $schedule->class_id);
                    })
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
