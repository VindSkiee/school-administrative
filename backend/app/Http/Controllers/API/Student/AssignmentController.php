<?php

namespace App\Http\Controllers\API\Student;

use App\Models\Assignment;
use App\Models\Schedule;
use App\Http\Requests\Student\StoreSubmissionRequest;
use App\Services\AssignmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController
{
    public function __construct(protected AssignmentService $assignmentService) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        /** @var \App\Models\User $user */
        $student = $user->student()->with('classes')->first();
        $activeClass = $student->classes->first();

        if (!$activeClass) {
            return response()->json(['error' => 'Anda tidak terdaftar di kelas aktif manapun.'], 403);
        }

        // 1. Ambil tugas berdasarkan kelas aktif siswa
        $query = Assignment::with([
            'schedule.subject',
            'schedule.teacher.user',
            'submissions' => function ($q) use ($student) {
                $q->where('student_id', $student->user_id)->with('grade');
            },
        ])
        ->whereHas('schedule', function ($q) use ($activeClass) {
            $q->where('class_id', $activeClass->id);
        });

        // 2. Filter by subject: when schedule_id is given, show assignments for the same
        // subject across ALL schedules in this class (not just one schedule slot).
        // This ensures students see all assignments even when a subject has multiple time slots.
        if ($request->filled('schedule_id')) {
            $schedule = Schedule::where('id', $request->schedule_id)
                ->where('class_id', $activeClass->id)
                ->first();

            if ($schedule) {
                $subjectScheduleIds = Schedule::where('class_id', $activeClass->id)
                    ->where('subject_id', $schedule->subject_id)
                    ->pluck('id');

                $query->whereIn('schedule_id', $subjectScheduleIds);
            } else {
                $query->where('schedule_id', $request->schedule_id);
            }
        }

        // 3. Filter Search (Judul/Deskripsi)
        // PERF FIX: replaced LIKE '%...%' with FULLTEXT search (uses ft_assignments_search index)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereFullText(['title', 'description'], $search);
        }

        // 4. Filter Tanggal Upload
        // PERF FIX: replaced whereDate() (function wrap, non-sargable) with whereBetween (uses idx_assignments_created_at)
        if ($request->filled('date')) {
            $date = Carbon::parse($request->date);
            $query->whereBetween('created_at', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ]);
        }

        // PERF FIX: replaced ->get() with ->paginate() — prevents loading all rows into memory
        $assignments = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->query('per_page', 15), 50));

        // Format ulang (mapping) data submission agar frontend lebih mudah membacanya
        $assignments->getCollection()->transform(function ($assignment) {
            $assignment->submission = $assignment->submissions->first() ?? null;
            unset($assignment->submissions);
            return $assignment;
        });

        return response()->json($assignments);
    }

    public function submit(StoreSubmissionRequest $request, string $id): JsonResponse
    {
        $user = auth('api')->user();
        /** @var \App\Models\User $user */
        $student = $user->student()->with('classes')->first();
        $activeClass = $student->classes->first();

        if (!$activeClass) {
            return response()->json(['error' => 'Anda tidak terdaftar di kelas manapun.'], 403);
        }

        try {
            $submission = $this->assignmentService->submitAssignment(
                $student->user_id,
                $activeClass->id,
                $id,
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Jawaban tugas berhasil diunggah.',
                'data' => $submission,
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
