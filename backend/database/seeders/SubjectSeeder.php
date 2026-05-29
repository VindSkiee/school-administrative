<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['code' => 'PAI-01', 'name' => 'Pendidikan Agama dan Budi Pekerti'],
            ['code' => 'PKN-01', 'name' => 'Pendidikan Pancasila dan Kewarganegaraan'],
            ['code' => 'BIN-01', 'name' => 'Bahasa Indonesia'],
            ['code' => 'MTK-01', 'name' => 'Matematika'],
            ['code' => 'IPA-01', 'name' => 'Ilmu Pengetahuan Alam'],
            ['code' => 'IPS-01', 'name' => 'Ilmu Pengetahuan Sosial'],
            ['code' => 'BIG-01', 'name' => 'Bahasa Inggris'],
            ['code' => 'SBD-01', 'name' => 'Seni Budaya'],
            ['code' => 'PJK-01', 'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan'],
            ['code' => 'PRK-01', 'name' => 'Prakarya'],
            ['code' => 'BSN-01', 'name' => 'Muatan Lokal (Bahasa Sunda)'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['code' => $subject['code']],
                ['name' => $subject['name']]
            );
        }

        $this->command->info('Master Data Mata Pelajaran berhasil dibuat.');
    }
}
