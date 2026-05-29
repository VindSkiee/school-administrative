<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Teacher; // Pastikan model Teacher Anda sudah ada
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Format Indonesia
        $classes = SchoolClass::all();
        $defaultPassword = Hash::make('password123');

        // Jumlah Guru = Jumlah Kelas (24) + Ekstra untuk Mapel (11)
        $totalTeachers = $classes->count() + 11;
        $teachers = [];

        for ($i = 0; $i < $totalTeachers; $i++) {
            // Generate Nama & Gender (Meskipun tabel teacher mungkin tidak punya kolom gender, ini untuk variasi nama)
            $gender = $faker->randomElement(['L', 'P']);
            $name = $gender === 'L' ? $faker->firstName('male').' '.$faker->lastName('male') : $faker->firstName('female').' '.$faker->lastName('female');

            // Tambahkan gelar akademik secara random
            $degree = $faker->randomElement([', S.Pd.', ', M.Pd.', ', S.Si.', ', S.Ag.']);
            $fullName = $name.$degree;

            $cleanName = preg_replace('/[^a-zA-Z]/', '', strtolower($name));
            $email = $cleanName.$faker->unique()->randomNumber(3).'@guru.sekolah.com';

            // 1. Buat Akun
            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'password' => $defaultPassword,
                'role' => 'teacher',
            ]);

            // 2. Buat Profil Teacher
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'nip' => $faker->unique()->numerify('198#######200#'), // NIP Acak 14 digit
            ]);

            $teachers[] = $teacher;
        }

        // 3. Tugaskan Guru sebagai Wali Kelas (Secara Berurutan)
        foreach ($classes as $index => $schoolClass) {
            // Kita ambil guru dari index 0 sampai 23 (Sesuai jumlah kelas)
            $schoolClass->update([
                'homeroom_teacher_id' => $teachers[$index]->user_id,
            ]);
        }

        $this->command->info("✅ Berhasil membuat {$totalTeachers} Guru dan menetapkan ".$classes->count().' di antaranya sebagai Wali Kelas.');
    }
}
