<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Master Data Pengguna & Mapel (Tidak butuh relasi lain)
            AdminSeeder::class,
            SubjectSeeder::class,

            // 2. Master Kelas (Akan membuat Tahun Ajaran & Daftar Kelas)
            ClassSeeder::class,
            ReportCardUnpublishedSeeder::class,
        ]);
    }
}
