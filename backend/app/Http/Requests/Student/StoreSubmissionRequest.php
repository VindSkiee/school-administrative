<?php

namespace App\Http\Requests\Student;

use App\Rules\SafeUpload;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,zip,png,jpg,jpeg', 'max:10240', new SafeUpload],
        ];
    }
}
