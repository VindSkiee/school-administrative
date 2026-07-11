<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserService
{
    public function createUser(array $data): User
    {
        $defaultPassword = (string) config('auth.default_user_password', 'password123');

        if ($defaultPassword === '') {
            $defaultPassword = 'password123';
        }

        if ($data['role'] === 'admin') {
            if (! isset($data['admin_secret_key']) || $data['admin_secret_key'] !== config('app.admin_secret_key', '')) {
                throw new HttpException(403, 'Akses ditolak: Admin Secret Key tidak valid.');
            }
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($defaultPassword),
                'is_active' => true,
                'must_change_password' => true,
            ]);

            // Set role explicitly — not mass-assigned for security
            $user->role = $data['role'];
            $user->save();

            if ($data['role'] === 'student') {
                $user->student()->create([
                    'nisn' => $data['nisn'],
                    'nis' => $data['nis'],
                    'gender' => $data['gender'],
                    'status' => 'active',
                ]);
            } elseif ($data['role'] === 'teacher') {
                $user->teacher()->create(['nip' => $data['nip'] ?? null]);
            } elseif ($data['role'] === 'admin') {
                $user->admin()->create(['nip' => $data['nip']]);
            } elseif ($data['role'] === 'principal') {
                $user->principal()->create(['nip' => $data['nip']]);
            }

            DB::commit();

            return $user->load(['student', 'teacher', 'admin', 'principal']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
