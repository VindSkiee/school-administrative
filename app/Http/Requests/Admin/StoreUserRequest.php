<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi sudah ditangani oleh Middleware Role di rute
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['student', 'teacher', 'admin', 'principal'])],
            
            // Validasi Kondisional untuk Profil
            'nisn' => ['required_if:role,student', 'string', 'unique:students,nisn'],
            'nip' => ['required_if:role,teacher', 'string', 'nullable', 'unique:teachers,nip'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.required_if' => 'NISN wajib diisi jika role adalah siswa.',
            'nip.required_if' => 'NIP wajib diisi jika role adalah guru.',
        ];
    }
}