<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserService
{
    /**
     * Memproses pembuatan user beserta relasi profilnya.
     */
    public function createUser(array $data): User
    {
        DB::beginTransaction();

        try {
            // 1. Buat Data Induk User
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'is_active' => true,
            ]);

            // 2. Buat Data Profil Berdasarkan Role
            if ($data['role'] === 'student') {
                $user->student()->create([
                    'nisn' => $data['nisn'],
                    // class_id sengaja null di awal, akan di-assign di fitur Class Management nanti
                ]);
            } elseif ($data['role'] === 'teacher') {
                $user->teacher()->create([
                    'nip' => $data['nip'] ?? null,
                ]);
            }

            DB::commit();

            // Kembalikan user beserta relasi profilnya
            return $user->load(['student', 'teacher']);
            
        } catch (Exception $e) {
            DB::rollBack();
            // Lemparkan kembali error ke atas agar ditangkap oleh Exception Handler Laravel
            throw $e; 
        }
    }
}