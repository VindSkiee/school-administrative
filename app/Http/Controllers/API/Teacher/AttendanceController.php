<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\Schedule;
use App\Models\Student;
use App\Http\Requests\Teacher\StoreBulkAttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AttendanceController
{
    public function __construct(protected AttendanceService $attendanceService) {}

    // GET: Lihat jadwal guru yang login pada hari ini
    public function getTodaySchedules(): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $today = strtolower(Carbon::today()->englishDayOfWeek); // Output: monday, tuesday, etc.

        $schedules = Schedule::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $today)
            // Relasi ke Academic Year aktif bisa ditambahkan jika perlu
            ->orderBy('start_time')
            ->get();

        return response()->json($schedules);
    }

    // GET: Mengambil daftar siswa untuk form absensi
    public function getStudentsForAttendance(string $scheduleId): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $schedule = Schedule::findOrFail($scheduleId);

        if ($schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        // Ambil HANYA siswa yang statusnya active di kelas tersebut
        $students = Student::with('user:id,name')
            ->where('class_id', $schedule->class_id)
            ->where('status', 'active')
            ->orderBy('nisn')
            ->get();

        return response()->json($students);
    }

    // POST: Submit absensi
    public function storeBulk(StoreBulkAttendanceRequest $request): JsonResponse
    {
        $teacherId = auth('api')->user()->id; // PK Teacher adalah id dari tabel Users

        try {
            $this->attendanceService->storeBulkAttendance($teacherId, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Data absensi kelas berhasil disimpan.'
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}