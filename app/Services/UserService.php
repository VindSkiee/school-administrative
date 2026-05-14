<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserService
{
    public function createUser(array $data): User
    {

        if ($data['role'] === 'admin') {
            if (!isset($data['admin_secret_key']) || $data['admin_secret_key'] !== env('ADMIN_SECRET_KEY')) {
                throw new HttpException(403, 'Akses ditolak: Admin Secret Key tidak valid.');
            }
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'is_active' => true,
            ]);

            if ($data['role'] === 'student') {
                $user->student()->create(['nisn' => $data['nisn']]);
            } elseif ($data['role'] === 'teacher') {
                $user->teacher()->create(['nip' => $data['nip'] ?? null]);
            }

            DB::commit();
            return $user->load(['student', 'teacher']);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e; 
        }
    }
}