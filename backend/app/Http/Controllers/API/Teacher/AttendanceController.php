<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Requests\Teacher\StoreBulkAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\MeetingSession;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AttendanceController
{
    public function __construct(protected AttendanceService $attendanceService) {}

    // GET: Lihat jadwal guru yang login pada hari ini
    public function getTodaySchedules(Request $request): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        $requestedDay = $request->query('day');
        $today = $requestedDay ? strtolower($requestedDay) : strtolower(Carbon::today()->englishDayOfWeek);
        $activeAcademicYearId = AcademicYear::where('is_active', true)->value('id');

        $schedules = Schedule::with(['schoolClass', 'subject'])
            ->withCount('meetingSessions as meeting_total')
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $today)
            ->where('academic_year_id', $activeAcademicYearId)
            ->orderBy('start_time')
            ->get();

        if ($requestedDay && $activeAcademicYearId) {
            $targetDate = $this->resolveDateForDay($requestedDay);

            if ($targetDate) {
                $sessionMap = MeetingSession::query()
                    ->where('date', $targetDate)
                    ->whereIn('schedule_id', $schedules->pluck('id')->toArray())
                    ->get()
                    ->keyBy('schedule_id');

                $schedules = $schedules->map(function ($schedule) use ($sessionMap) {
                    $session = $sessionMap->get($schedule->id);
                    $schedule->meeting_session = $session;
                    $schedule->is_holiday = $session && $session->status === 'holiday';

                    return $schedule;
                });
            }
        }

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

        // PERBAIKAN: Gunakan whereHas untuk menembus tabel pivot 'class_student'
        $students = Student::with('user:id,name')
            ->whereHas('classes', function ($query) use ($schedule) {
                // $query ini sekarang berada di dalam scope tabel 'classes'
                $query->where('classes.id', $schedule->class_id);
            })
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
                'message' => 'Data absensi kelas berhasil disimpan.',
            ]);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    // GET: Mengambil data absensi yang sudah tersimpan hari ini
    public function getAttendances(Request $request, string $scheduleId): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|date_format:Y-m-d',
        ]);

        $teacherId = auth('api')->user()->id;
        $schedule = Schedule::findOrFail($scheduleId);

        // Validasi kepemilikan jadwal
        if ($schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        // Ambil data absensi berdasarkan jadwal dan tanggal
        $attendances = Attendance::query()
            ->where('schedule_id', $scheduleId)
            ->where('date', $request->date)
            ->get();

        return response()->json($attendances);
    }

    // GET: Mengambil detail spesifik satu jadwal mengajar
    public function show(Request $request, string $scheduleId): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        // Tarik jadwal beserta relasi kelas dan mapelnya
        $schedule = Schedule::with(['schoolClass', 'subject'])
            ->withCount('meetingSessions as meeting_total')
            ->findOrFail($scheduleId);

        // Proteksi keamanan: pastikan guru yang mengakses adalah pemilik jadwal ini
        if ($schedule->teacher_id !== $teacherId) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        // Attach meeting session + holiday status when date is provided
        $date = $request->query('date');
        if ($date) {
            $session = MeetingSession::query()
                ->where('schedule_id', $scheduleId)
                ->where('date', $date)
                ->first();

            $schedule->meeting_session = $session;
            $schedule->is_holiday = $session && $session->status === 'holiday';
        }

        return response()->json($schedule);
    }

    private function resolveDateForDay(string $day): ?string
    {
        $today = Carbon::today();
        $dayMap = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];
        $dayIndex = $dayMap[$day] ?? null;

        if ($dayIndex === null) {
            return null;
        }

        $todayIndex = $today->dayOfWeek;

        if ($dayIndex === $todayIndex) {
            return $today->toDateString();
        }

        if ($dayIndex > $todayIndex) {
            return $today->copy()->addDays($dayIndex - $todayIndex)->toDateString();
        }

        return $today->copy()->subDays($todayIndex - $dayIndex)->toDateString();
    }
}
