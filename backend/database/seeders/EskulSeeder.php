<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Eskul;
use App\Models\StudentEskul;
use App\Models\User;
use Illuminate\Database\Seeder;

class EskulSeeder extends Seeder
{
    public function run(): void
    {
        $teacherIds = User::where('role', 'teacher')->pluck('id')->toArray();
        $totalTeachers = count($teacherIds);

        $eskulData = [
            ['name' => 'Pramuka', 'description' => 'Kegiatan Kepramukaan untuk membina karakter disiplin, tanggung jawab, dan kemandirian siswa.'],
            ['name' => 'Paskibra', 'description' => 'Latihan baris-berbaris dan kepemimpinan untuk menguatkan nasionalisme dan kerja sama tim.'],
            ['name' => 'Palang Merah Remaja (PMR)', 'description' => 'Kegiatan sosial dan kemanusiaan yang mengajarkan pertolongan pertama dan kepedulian sosial.'],
            ['name' => 'Rohani Islam (Rohis)', 'description' => 'Kegiatan kerohanian Islam yang mencakup tafiz, kajian, dan diskusi keagamaan.'],
            ['name' => 'Basket', 'description' => 'Latihan dan pertandingan bola basket untuk mengembangkan sportivitas dan kebugaran.'],
            ['name' => 'Futsal', 'description' => 'Latihan dan pertandingan futsal untuk membangun kerja sama tim dan daya juang.'],
            ['name' => 'Voli', 'description' => 'Latihan dan pertandingan bola voli untuk meningkatkan kekompakan dan ketangkasan.'],
            ['name' => 'Pencak Silat', 'description' => 'Seni bela diri tradisional yang mengembangkan fisik, mental, dan karakter siswa.'],
            ['name' => 'Tari Tradisional', 'description' => 'Pelestarian dan pengembangan seni tari daerah Nusantara.'],
            ['name' => 'Paduan Suara', 'description' => 'Latihan vokal dan seni musik paduan suara untuk mengembangkan apresiasi seni.'],
            ['name' => 'English Club', 'description' => 'Klub bahasa Inggris untuk meningkatkan kemampuan berkomunikasi internasional.'],
            ['name' => 'Karya Ilmiah Remaja (KIR)', 'description' => 'Eksplorasi sains, penelitian, dan inovasi melalui kegiatan ilmiah.'],
        ];

        $eskuls = [];
        foreach ($eskulData as $index => $data) {
            $teacherId = $totalTeachers > 0 ? $teacherIds[$index % $totalTeachers] : null;

            $eskul = Eskul::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'teacher_id' => $teacherId,
                    'is_active' => true,
                ]
            );
            $eskuls[$eskul->name] = $eskul;
        }

        $this->command->info('✅ 12 data Ekstrakurikuler dibuat (dengan PIC guru).');

        // --- Student Enrollment ---
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            $this->command->warn('⚠️ Tidak ada tahun ajaran aktif, skip student enrollment.');

            return;
        }

        $studentIds = User::where('role', 'student')->pluck('id')->toArray();
        if (empty($studentIds)) {
            $this->command->warn('⚠️ Tidak ada siswa, skip student enrollment.');

            return;
        }

        $allEskulNames = array_keys($eskuls);
        $basketEskul = $eskuls['Basket'];
        $nonBasketEskuls = array_filter($eskuls, fn ($e) => $e->name !== 'Basket');
        $graderUserId = $teacherIds[0] ?? null;

        $enrollmentCount = 0;
        $gradedCount = 0;
        $ungradedCount = 0;
        $ungradedBasketCount = 0;
        $ungradedBasketTarget = 4;

        foreach ($studentIds as $studentId) {
            // 80% siswa daftar eskul, 20% skip
            if (rand(1, 100) > 80) {
                continue;
            }

            // Tentukan jumlah eskul: 60% daftar 1 eskul, 40% daftar 2 eskul
            $count = rand(1, 100) <= 60 ? 1 : 2;

            // Pilih eskul secara random (pastikan Pramuka sering muncul)
            $chosenNames = [];
            if (rand(1, 100) <= 50) {
                // 50% chance: Pramuka sebagai eskul pertama
                $chosenNames[] = 'Pramuka';
            }

            while (count($chosenNames) < $count) {
                $pick = $allEskulNames[array_rand($allEskulNames)];
                if (! in_array($pick, $chosenNames, true)) {
                    $chosenNames[] = $pick;
                }
            }

            foreach ($chosenNames as $eskulName) {
                $eskul = $eskuls[$eskulName];

                // Cek apakah sudah daftar (unique constraint)
                $exists = StudentEskul::where('student_id', $studentId)
                    ->where('eskul_id', $eskul->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // 5% ungraded —全部 di eskul "Basket" saja
                $shouldBeUngraded = $eskulName === 'Basket'
                    && $ungradedBasketCount < $ungradedBasketTarget;

                $score = $shouldBeUngraded ? null : rand(70, 95);
                $gradedAt = $shouldBeUngraded ? null : now();
                $gradedBy = $shouldBeUngraded ? null : $graderUserId;

                StudentEskul::create([
                    'student_id' => $studentId,
                    'eskul_id' => $eskul->id,
                    'academic_year_id' => $activeYear->id,
                    'score' => $score,
                    'graded_at' => $gradedAt,
                    'graded_by' => $gradedBy,
                ]);

                $enrollmentCount++;

                if ($shouldBeUngraded) {
                    $ungradedCount++;
                    $ungradedBasketCount++;
                } else {
                    $gradedCount++;
                }
            }
        }

        $this->command->info("✅ {$enrollmentCount} Student Eskul enrollments dibuat.");
        $this->command->info("   → {$gradedCount} sudah dinilai, {$ungradedCount} belum dinilai (semua di Basket).");
    }
}
