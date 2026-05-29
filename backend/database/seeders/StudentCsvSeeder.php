<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; // <-- Tambahkan Faker

class StudentCsvSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('data/students.csv');

        if (! file_exists($filePath)) {
            $this->command->error("File CSV tidak ditemukan di {$filePath}.");

            return;
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            $this->command->error('Tidak ada Tahun Ajaran yang aktif.');

            return;
        }

        $file = fopen($filePath, 'r');

        // 1. AUTO-DETECT SEPARATOR (Koma atau Titik Koma)
        $firstLine = fgets($file);
        $separator = strpos($firstLine, ';') !== false ? ';' : ',';

        rewind($file);
        $header = fgetcsv($file, 0, $separator); // Lewati header

        $defaultPassword = Hash::make('password123');
        $count = 0;
        $rowNumber = 2;

        // Inisialisasi Faker dengan Bahasa Indonesia
        $faker = Faker::create('id_ID');

        while (($row = fgetcsv($file, 0, $separator)) !== false) {
            // Mapping index bergeser karena kolom 'name' tidak ada di CSV
            // Asumsi Format CSV Baru: 0:nisn, 1:nis, 2:gender, 3:class_name
            $nisn = trim($row[0] ?? '');
            $nis = trim($row[1] ?? '');

            // Ambil gender terlebih dahulu untuk menentukan nama
            $genderRaw = trim($row[2] ?? '');
            $gender = strtoupper($genderRaw) === 'L' ? 'L' : 'P';

            $className = trim($row[3] ?? '');

            // 2. DEBUGGING: Jika NIS kosong, lewati
            if (empty($nis)) {
                $this->command->warn("⚠️ Baris ke-{$rowNumber} dilewati. NIS kosong. Data: ".json_encode($row));
                $rowNumber++;

                continue;
            }

            // 3. GENERATE NAMA RANDOM BERDASARKAN GENDER
            if ($gender === 'L') {
                $name = $faker->firstName('male').' '.$faker->lastName('male');
            } else {
                $name = $faker->firstName('female').' '.$faker->lastName('female');
            }

            // Bersihkan nama dari tanda kutip/titik (misal nama Faker: "Dr. Budi", "D'Amore") untuk Email
            $cleanName = preg_replace('/[^a-zA-Z]/', '', strtolower($name));
            $email = $cleanName.Str::random(3).'@student.sekolah.com';

            // 4. Buat Akun User
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name, // Menggunakan nama random
                    'password' => $defaultPassword,
                    'role' => 'student',
                ]
            );

            // 5. Buat Profil Student
            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nisn' => $nisn,
                    'nis' => $nis,
                    'gender' => $gender,
                    'status' => 'active',
                ]
            );

            // 6. Masukkan ke Kelas (Pivot Table)
            $class = SchoolClass::where('name', $className)
                ->where('academic_year_id', $activeYear->id)
                ->first();

            if ($class) {
                $student->classes()->syncWithoutDetaching([
                    $class->id => [
                        'academic_year_id' => $activeYear->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            } else {
                $this->command->warn("⚠️ Kelas '{$className}' untuk siswa {$name} (NIS: {$nis}) tidak ditemukan.");
            }

            $count++;
            $rowNumber++;
        }

        fclose($file);
        $this->command->info("✅ Berhasil mengimpor {$count} siswa dengan nama acak ke dalam kelas masing-masing.");
    }
}
