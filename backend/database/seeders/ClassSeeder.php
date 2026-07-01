<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan ada minimal 1 Tahun Ajaran Aktif
        $activeYear = AcademicYear::firstOrCreate(
            ['name' => '2025/2026', 'semester' => 'odd'],
            [
                'is_active' => true,
                'start_date' => '2025-07-07',
                'end_date' => '2025-12-19',
            ]
        );

        // 2. Daftar kelas berdasarkan file Excel yang Anda berikan
        $classNames = [
            '7A', '7B', '7C', '7D', '7E', '7F', '7G', '7H',
            '8A', '8B', '8C', '8D', '8E', '8F', '8G', '8H',
            '9A', '9B', '9C', '9D', '9E', '9F', '9G', '9H',
        ];

        foreach ($classNames as $name) {
            SchoolClass::firstOrCreate([
                'name' => $name,
                'academic_year_id' => $activeYear->id,
            ]);
        }

        $this->command->info('Master Data Kelas berhasil dibuat untuk Tahun Ajaran '.$activeYear->name);
    }
}
