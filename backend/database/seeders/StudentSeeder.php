<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Format Indonesia
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (! $activeYear) {
            $this->command->error('Tidak ada Tahun Ajaran yang aktif!');

            return;
        }

        $classes = SchoolClass::where('academic_year_id', $activeYear->id)->get();
        $defaultPassword = Hash::make('password123');

        $totalStudents = 0;

        foreach ($classes as $schoolClass) {
            // LOOPING: Buat tepat 40 siswa untuk setiap kelas
            for ($i = 1; $i <= 40; $i++) {
                // 1. Tentukan Gender dulu
                $gender = $faker->randomElement(['L', 'P']);

                // 2. Buat Nama sesuai Gender
                $name = $gender === 'L' ? $faker->firstName('male').' '.$faker->lastName('male') : $faker->firstName('female').' '.$faker->lastName('female');

                $cleanName = preg_replace('/[^a-zA-Z]/', '', strtolower($name));
                $email = $cleanName.$faker->unique()->randomNumber(4).'@student.sekolah.com';

                // 3. Buat Akun
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $defaultPassword,
                    'role' => 'student',
                ]);

                // 4. Buat Profil Student (NIS & NISN Acak)
                $student = Student::create([
                    'user_id' => $user->id,
                    'nisn' => $faker->unique()->numerify('00########'), // 10 Digit
                    'nis' => $faker->unique()->numerify('2425#####'),  // 9 Digit
                    'gender' => $gender,
                    'status' => 'active',
                ]);

                // 5. Masukkan ke Kelas (Arsitektur Pivot)
                $student->classes()->attach($schoolClass->id, [
                    'academic_year_id' => $activeYear->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalStudents++;
            }
        }

        $this->command->info("✅ Berhasil membuat {$totalStudents} Siswa (40 Siswa x ".$classes->count().' Kelas) dan memetakannya secara histori.');
    }
}
