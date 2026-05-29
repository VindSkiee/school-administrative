<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->input('role');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['student', 'teacher', 'admin', 'principal'])],
            'admin_secret_key' => ['required_if:role,admin', 'string'],
        ];

        // Default optional fields
        $rules['nisn'] = ['nullable', 'string', 'max:50'];
        $rules['nis'] = ['nullable', 'string', 'max:50'];
        $rules['gender'] = ['nullable', Rule::in(['L', 'P'])];

        if ($role === 'student') {
            $rules['nisn'] = ['required', 'string', 'max:50', Rule::unique('students', 'nisn')];
            $rules['nis'] = ['required', 'string', 'max:50', Rule::unique('students', 'nis')];
            $rules['gender'] = ['required', Rule::in(['L', 'P'])];
        }

        $rules['nip'] = ['nullable', 'string', 'max:50'];
        if ($role === 'teacher') {
            $rules['nip'] = ['required', 'string', 'max:50', Rule::unique('teachers', 'nip')];
        } elseif ($role === 'admin') {
            $rules['nip'] = ['required', 'string', 'max:50', Rule::unique('admins', 'nip')];
        } elseif ($role === 'principal') {
            $rules['nip'] = ['required', 'string', 'max:50', Rule::unique('principals', 'nip')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nisn.required' => 'NISN wajib diisi jika role adalah siswa.',
            'nis.required' => 'NIS wajib diisi jika role adalah siswa.',
            'gender.required' => 'Jenis kelamin wajib diisi jika role adalah siswa.',
            'gender.in' => 'Jenis kelamin harus L atau P.',
            'nip.required' => 'NIP wajib diisi untuk role guru, admin, atau kepala sekolah.',
            'admin_secret_key.required_if' => 'Key rahasia admin wajib diisi dalam membuat akun admin.',
        ];
    }
}
