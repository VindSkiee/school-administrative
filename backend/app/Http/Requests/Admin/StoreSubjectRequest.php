<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dihandle middleware
    }

    protected function prepareForValidation()
    {
        // Auto-uppercase dan trim kode mapel sebelum divalidasi
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject'); // Ambil ID dari URL jika sedang proses Update

        return [
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code,' . $subjectId],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}