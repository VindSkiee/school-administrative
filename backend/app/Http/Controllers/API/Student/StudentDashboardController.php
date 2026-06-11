<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\User; // <-- 1. TAMBAHKAN IMPORT INI
use App\Services\StudentDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudentDashboardController extends Controller
{
    public function __construct(protected StudentDashboardService $dashboardService) {}

    public function index(): JsonResponse
    {
        /** @var User $user */ // <-- 2. TAMBAHKAN DOCBLOCK INI
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Sesi habis.'], 401);
        }

        // Garis merah di bawah 'student' pasti sudah hilang sekarang!
        $student = $user->student()->with('classes')->first();

        // Cari tahu apakah student ada dan aktif
        if (!$student || strtolower($student->status) !== 'active') {
            throw new HttpException(403, 'Akun siswa tidak aktif.');
        }

        // Ambil kelas pertama (aktif) dari relasi Many-to-Many
        $activeClass = $student->classes->first();

        // Validasi jika siswa belum dimasukkan ke tabel 'class_student'
        if (!$activeClass) {
            throw new HttpException(403, 'Siswa belum terdaftar di kelas manapun.');
        }

        try {
            // Lempar student DAN kelas aktifnya ke Service
            $data = $this->dashboardService->getDashboardData($student, $activeClass->id);

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat data dashboard: ' . $e->getMessage()], 500);
        }
    }
}