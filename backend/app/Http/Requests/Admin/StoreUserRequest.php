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
            'role' => ['required', \Illuminate\Validation\Rule::in(['student', 'teacher', 'admin', 'principal'])],
            
            // Profil conditional
            'nisn' => ['required_if:role,student', 'string', 'unique:students,nisn'],
            'nip' => ['required_if:role,teacher', 'string', 'nullable', 'unique:teachers,nip'],

            // Logic Key Otorisasi Admin Baru
            'admin_secret_key' => ['required_if:role,admin', 'string']
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.required_if' => 'NISN wajib diisi jika role adalah siswa.',
            'nip.required_if' => 'NIP wajib diisi jika role adalah guru.',
            'admin_secret_key.required_if' => 'Key rahasia admin wajib diisi dalam membuat akun admin.',
        ];
    }
}