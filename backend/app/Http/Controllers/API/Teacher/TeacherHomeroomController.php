<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherHomeroomController
{
    public function show(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        // Cari data guru beserta kelas perwalian dan relasi siswanya
        $teacher = \App\Models\Teacher::with([
            'homeroomClass.students' => function ($query) {
                $query->where('status', 'active'); // Hanya siswa aktif
            },
            'homeroomClass.students.user', 
            'homeroomClass.students.attendances', 
            'homeroomClass.students.submissions.grade' // Untuk mengambil nilai
        ])->where('user_id', $teacherId)->first();

        if (!$teacher || !$teacher->homeroomClass) {
            return response()->json(['error' => 'Anda belum ditetapkan sebagai Wali Kelas.'], 403);
        }

        $class = $teacher->homeroomClass;

        // Mapping dan Kalkulasi Data Siswa
        $studentsData = $class->students->map(function ($student) {
            $attendances = $student->attendances;
            $totalAttendances = $attendances->count();

            $present = $attendances->where('status', 'present')->count();
            $sick = $attendances->where('status', 'sick')->count();
            $permission = $attendances->where('status', 'permission')->count();
            $alpa = $attendances->where('status', 'alpa')->count();

            // Hitung persentase kehadiran
            $attendanceRate = $totalAttendances > 0 
                ? round(($present / $totalAttendances) * 100, 2) 
                : 100; // Default 100% jika belum ada absensi

            // Hitung rata-rata nilai dari seluruh tugas yang sudah dinilai
            $grades = $student->submissions->map->grade->filter();
            $averageScore = $grades->count() > 0 
                ? round($grades->avg('score'), 2) 
                : 0;

            return [
                // 🌟 PERBAIKAN DI SINI: Ekstrak ID langsung dari relasi user atau foreign key
                'id' => $student->user_id ?? $student->user->id,
                'user_id' => $student->user_id ?? $student->user->id,
                
                'nis' => $student->nis ?? '-',
                'name' => $student->user->name ?? 'Tanpa Nama',
                'gender' => $student->gender ?? 'L',
                'status' => $student->status,
                'present' => $present,
                'sick' => $sick,
                'permission' => $permission,
                'alpa' => $alpa,
                'attendance_rate' => $attendanceRate,
                'math_score' => $averageScore, 
                'bio_score' => $averageScore,  
                'average_score' => $averageScore,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'class_info' => [
                'id' => $class->id,
                'name' => $class->name,
                'total_students' => $studentsData->count(),
            ],
            'students' => $studentsData
        ]);
    }
}