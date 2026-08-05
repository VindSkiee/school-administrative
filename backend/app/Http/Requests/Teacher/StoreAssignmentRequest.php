<?php

namespace App\Http\Requests\Teacher;

use App\Models\AcademicYear;
use App\Rules\SafeUpload;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'type' => ['required', 'string', 'in:task,ujian_harian,uts,uas'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'due_date' => ['required', 'date', 'after:now'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg', 'max:10240', new SafeUpload],
            'enable_remedial' => ['nullable', 'boolean'],
            'remedial_mode' => ['required_if:enable_remedial,true', 'nullable', 'string', 'in:replace,average,custom'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $dueDate = $this->input('due_date');
            if (! $dueDate) {
                return;
            }

            $activeYear = AcademicYear::active();
            if (! $activeYear) {
                return;
            }

            $dueCarbon = Carbon::parse($dueDate);
            $yearEnd = Carbon::parse($activeYear->end_date);

            if ($dueCarbon->gt($yearEnd)) {
                $validator->errors()->add(
                    'due_date',
                    'Tenggat waktu melewati akhir periode tahun ajaran aktif ('.$yearEnd->format('d M Y').').'
                );
            }
        });
    }
}
