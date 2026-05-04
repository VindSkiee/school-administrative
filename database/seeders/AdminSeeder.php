<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {
        // Gunakan updateOrCreate untuk mencegah error jika seeder dijalankan ulang
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'], // Identifier unik
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('admin'), // Pastikan gunakan password kuat di production
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created successfully: admin@gmail.com');
    }
}