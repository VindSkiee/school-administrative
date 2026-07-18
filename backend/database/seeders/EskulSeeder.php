<?php

namespace Database\Seeders;

use App\Models\Eskul;
use App\Models\User;
use Illuminate\Database\Seeder;

class EskulSeeder extends Seeder
{
    public function run(): void
    {
        $teacherIds = User::where('role', 'teacher')->pluck('id')->toArray();
        $totalTeachers = count($teacherIds);

        $eskuls = [
            [
                'name' => 'Pramuka',
                'description' => 'Kegiatan Kepramukaan untuk membina karakter disiplin, tanggung jawab, dan kemandirian siswa.',
            ],
            [
                'name' => 'Paskibra',
                'description' => 'Latihan baris-berbaris dan kepemimpinan untuk menguatkan nasionalisme dan kerja sama tim.',
            ],
            [
                'name' => 'Palang Merah Remaja (PMR)',
                'description' => 'Kegiatan sosial dan kemanusiaan yang mengajarkan pertolongan pertama dan kepedulian sosial.',
            ],
            [
                'name' => 'Rohani Islam (Rohis)',
                'description' => 'Kegiatan kerohanian Islam yang mencakup tafiz, kajian, dan diskusi keagamaan.',
            ],
            [
                'name' => 'Basket',
                'description' => 'Latihan dan pertandingan bola basket untuk mengembangkan sportivitas dan kebugaran.',
            ],
            [
                'name' => 'Futsal',
                'description' => 'Latihan dan pertandingan futsal untuk membangun kerja sama tim dan daya juang.',
            ],
            [
                'name' => 'Voli',
                'description' => 'Latihan dan pertandingan bola voli untuk meningkatkan kekompakan dan ketangkasan.',
            ],
            [
                'name' => 'Pencak Silat',
                'description' => 'Seni bela diri tradisional yang mengembangkan fisik, mental, dan karakter siswa.',
            ],
            [
                'name' => 'Tari Tradisional',
                'description' => 'Pelestarian dan pengembangan seni tari daerah Nusantara.',
            ],
            [
                'name' => 'Paduan Suara',
                'description' => 'Latihan vokal dan seni musik paduan suara untuk mengembangkan apresiasi seni.',
            ],
            [
                'name' => 'English Club',
                'description' => 'Klub bahasa Inggris untuk meningkatkan kemampuan berkomunikasi internasional.',
            ],
            [
                'name' => 'Karya Ilmiah Remaja (KIR)',
                'description' => 'Eksplorasi sains, penelitian, dan inovasi melalui kegiatan ilmiah.',
            ],
        ];

        foreach ($eskuls as $index => $data) {
            $teacherId = $totalTeachers > 0 ? $teacherIds[$index % $totalTeachers] : null;

            Eskul::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'teacher_id' => $teacherId,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('12 data Ekstrakurikuler berhasil dibuat.');
    }
}
