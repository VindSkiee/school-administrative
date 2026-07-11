<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\MeetingSession;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
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

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil siswa tidak ditemukan.',
            ], 404);
        }

        // 3. Ambil ID kelas-kelas di mana siswa ini terdaftar
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $classIds = $student->classes()
            ->when($activeYearId, fn ($q) => $q->where('classes.academic_year_id', $activeYearId))
            ->pluck('classes.id');

        if ($classIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Anda belum terdaftar di kelas manapun.',
            ]);
        }

        // 4. Bangun Query Jadwal
        $query = Schedule::with(['subject', 'teacher.user', 'schoolClass'])
            ->withCount('meetingSessions as meeting_total')
            ->whereIn('class_id', $classIds);

        // 5. Filter berdasarkan hari jika parameter 'day' dikirim dari frontend
        $requestedDay = $request->query('day');
        if ($requestedDay && ! empty($requestedDay)) {
            $query->where('day_of_week', strtolower($requestedDay));
        }

        // 6. Urutkan dari jam paling pagi
        $schedules = $query->orderBy('start_time', 'asc')->get();

        // 7. Attach meeting session + holiday status when day is provided
        if ($requestedDay && $activeYearId) {
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

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'message' => 'Jadwal berhasil diambil.',
        ]);
    }

    /**
     * Mendapatkan detail satu jadwal spesifik untuk Header Halaman Detail.
     */
    public function show(Request $request, string $id)
    {
        // Ambil jadwal beserta detail mapel, kelas, dan nama user dari guru pengajar
        $schedule = Schedule::with(['subject', 'schoolClass', 'teacher.user'])
            ->withCount('meetingSessions as meeting_total')
            ->find($id);

        if (! $schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal pelajaran tidak ditemukan.',
            ], 404);
        }

        // Attach meeting session + holiday status when date is provided
        $date = $request->query('date');
        if ($date) {
            $session = MeetingSession::query()
                ->where('schedule_id', $id)
                ->where('date', $date)
                ->first();

            $schedule->meeting_session = $session;
            $schedule->is_holiday = $session && $session->status === 'holiday';
        }

        return response()->json([
            'success' => true,
            'data' => $schedule,
            'message' => 'Detail jadwal berhasil diambil.',
        ]);
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
