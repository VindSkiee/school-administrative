<?php
namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'type' => ['required', 'string', 'in:task,uts,uas'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'due_date' => ['required', 'date', 'after:now'],
            'files' => ['nullable', 'array', 'max:5'], // Maks 5 File
            'files.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg', 'max:10240'], 
        ];
    }
}