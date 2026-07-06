<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

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
            'files.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg', 'max:10240'],
            'enable_remedial' => ['nullable', 'boolean'],
            'remedial_mode' => ['required_if:enable_remedial,true', 'nullable', 'string', 'in:replace,average,custom'],
        ];
    }
}
