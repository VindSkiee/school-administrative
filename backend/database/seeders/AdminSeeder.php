<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Principal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminDataList = [
            ['name' => 'Euis Herlina', 'email' => 'euis.herlina@sekolah.com', 'nip' => '198601102010012001'],
            ['name' => 'Titin Sukayanti', 'email' => 'titin.sukayanti@sekolah.com', 'nip' => '196808042007012011'],
            ['name' => 'Siti Rodiyah', 'email' => 'siti.rodiyah@sekolah.com', 'nip' => '5743768670210002'],
        ];

        $defaultPassword = Hash::make('password123');

        DB::beginTransaction();
        try {
            // 1. GENERATE DATA ADMIN (3 AKUN)
            foreach ($adminDataList as $data) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => $defaultPassword,
                        'role' => 'admin',
                    ]
                );

                // Buat baris profile wajib di tabel admins
                Admin::firstOrCreate(
                    ['user_id' => $user->id],
                    ['nip' => $data['nip']]
                );
            }
            $this->command->info('✅ 3 Akun Admin beserta NIP berhasil dibuat.');

            // 2. GENERATE DATA PRINCIPAL (1 AKUN DEFAULT SEEDER SEBELUMNYA)
            $principalUser = User::firstOrCreate(
                ['email' => 'principal@sekolah.com'],
                [
                    'name' => 'Dr. Kepala Sekolah, M.Pd.',
                    'password' => $defaultPassword,
                    'role' => 'principal',
                ]
            );

            Principal::firstOrCreate(
                ['user_id' => $principalUser->id],
                ['nip' => '197503122002101002'] // Contoh NIP Kepala Sekolah
            );
            $this->command->info('✅ 1 Akun Principal beserta NIP berhasil dibuat.');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Gagal menjalankan seeder: '.$e->getMessage());
        }
    }
}
