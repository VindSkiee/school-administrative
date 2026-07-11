<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\Attendance;
use App\Models\MeetingSession;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceRecapController
{
    /**
     * GET /attendance-recap?academic_year_id=...
     * Return all schedules with meeting session attendance status for the logged-in teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ]);

        $teacherId = auth('api')->user()->id;
        $academicYearId = (int) $request->query('academic_year_id');

        $schedules = Schedule::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $scheduleIds = $schedules->pluck('id')->toArray();

        // Prefetch all meeting sessions for these schedules
        $meetingSessions = MeetingSession::whereIn('schedule_id', $scheduleIds)
            ->orderBy('date')
            ->get()
            ->groupBy('schedule_id');

        // Prefetch all attendance records for these schedules (to check which sessions are filled)
        $attendanceCounts = Attendance::whereIn('schedule_id', $scheduleIds)
            ->selectRaw('schedule_id, date, COUNT(*) as attendance_count')
            ->groupBy('schedule_id', 'date')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->schedule_id.'_'.$row->date => $row->attendance_count,
            ]);

        $result = $schedules->map(function ($schedule) use ($meetingSessions, $attendanceCounts) {
            $sessions = $meetingSessions->get($schedule->id, collect());

            $sessionData = $sessions->map(function ($session) use ($attendanceCounts, $schedule) {
                $dateKey = $schedule->id.'_'.$session->date->format('Y-m-d');
                $attendanceCount = $attendanceCounts->get($dateKey, 0);

                $status = 'missing';
                if ($session->status === 'holiday') {
                    $status = 'holiday';
                } elseif ($attendanceCount > 0) {
                    $status = 'completed';
                } elseif ($session->date->isFuture()) {
                    $status = 'upcoming';
                }

                return [
                    'date' => $session->date->format('Y-m-d'),
                    'meeting_number' => $session->meeting_number,
                    'status' => $status,
                    'attendance_count' => $attendanceCount,
                ];
            });

            $totalSessions = $sessions->count();
            $completedSessions = $sessionData->where('status', 'completed')->count();
            $missingSessions = $sessionData->where('status', 'missing')->count();

            return [
                'id' => $schedule->id,
                'class_name' => $schedule->schoolClass?->name ?? '-',
                'subject_name' => $schedule->subject?->name ?? '-',
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'missing_sessions' => $missingSessions,
                'sessions' => $sessionData->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result->values(),
        ]);
    }
}
