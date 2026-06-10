<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            
            // Validasi untuk array files (Maksimal 10 file)
            'files' => ['required', 'array', 'max:10'],
            // Validasi per-file: Maks 10MB, format khusus
            'files.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,mp4,zip,png,jpg,jpeg', 'max:10240'], 
        ];
    }
}