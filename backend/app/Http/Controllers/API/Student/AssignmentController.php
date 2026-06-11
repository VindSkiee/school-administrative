<?php

namespace App\Http\Controllers\API\Student;

use App\Models\Assignment;
use App\Http\Requests\Student\StoreSubmissionRequest;
use App\Services\AssignmentService;
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
            // Hanya ambil submission milik siswa ini saja, beserta nilainya (grade)
            'submissions' => function($q) use ($student) {
                $q->where('student_id', $student->user_id)->with('grade');
            }
        ])
        ->whereHas('schedule', function ($q) use ($activeClass) {
            $q->where('class_id', $activeClass->id);
        });

        // 2. Filter spesifik untuk Jadwal (Mata Pelajaran) di Ruang Kelas ini
        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->schedule_id);
        }

        // 3. Filter Search (Judul/Deskripsi)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 4. Filter Tanggal Upload
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Urutkan dari yang terbaru diunggah
        $assignments = $query->orderBy('created_at', 'desc')->get();

        // Format ulang (mapping) data submission agar frontend lebih mudah membacanya
        $formattedAssignments = $assignments->map(function ($assignment) {
            $assignment->submission = $assignment->submissions->first() ?? null;
            unset($assignment->submissions); // Buang array submissions agar bersih
            return $assignment;
        });

        return response()->json(['data' => $formattedAssignments]);
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
            // Service harus menerima parameter yang ada
            $submission = $this->assignmentService->submitAssignment(
                $student->user_id,
                $activeClass->id, // PERBAIKAN: Gunakan ID dari kelas aktif
                $id,
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Jawaban tugas berhasil diunggah.',
                'data' => $submission
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}