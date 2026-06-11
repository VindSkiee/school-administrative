<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentScheduleController extends Controller
{
    /**
     * Mendapatkan daftar jadwal pelajaran untuk siswa yang sedang login.
     */
    public function index(Request $request)
    {
        // 1. Dapatkan user yang sedang login
        $user = Auth::user();
        
        // 2. Ambil data Student terkait
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil siswa tidak ditemukan.'
            ], 404);
        }

        // 3. Ambil ID kelas-kelas di mana siswa ini terdaftar
        // Kita menggunakan pluck('classes.id') karena relasinya belongsToMany
        $classIds = $student->classes()->pluck('classes.id');

        if ($classIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Anda belum terdaftar di kelas manapun.'
            ]);
        }

        // 4. Bangun Query Jadwal
        // Eager load subject, guru (teacher.user untuk ambil nama), dan kelas
        $query = Schedule::with(['subject', 'teacher.user', 'schoolClass'])
            ->whereIn('class_id', $classIds);

        // 5. Filter berdasarkan hari jika parameter 'day' dikirim dari frontend
        if ($request->has('day') && !empty($request->day)) {
            $query->where('day_of_week', strtolower($request->day));
        }

        // 6. Urutkan dari jam paling pagi
        $schedules = $query->orderBy('start_time', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'message' => 'Jadwal berhasil diambil.'
        ]);
    }

    /**
     * Mendapatkan detail satu jadwal spesifik untuk Header Halaman Detail.
     */
    public function show(string $id)
    {
        // Ambil jadwal beserta detail mapel, kelas, dan nama user dari guru pengajar
        $schedule = Schedule::with(['subject', 'schoolClass', 'teacher.user'])
            ->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal pelajaran tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule,
            'message' => 'Detail jadwal berhasil diambil.'
        ]);
    }
}