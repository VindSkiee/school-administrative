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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'due_date' => ['required', 'date', 'after:now'], // Tenggat waktu tidak boleh di masa lalu
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,zip', 'max:10240'], // File lampiran soal (opsional)
        ];
    }
}