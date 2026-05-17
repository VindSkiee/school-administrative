<?php

namespace App\Http\Controllers\API\Student;

use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController
{
    public function index(): JsonResponse
    {
        // Ambil data siswa dari relasi user yang sedang login
        $student = auth('api')->user()->student;

        // Siswa hanya boleh melihat materi jika mereka masih aktif dan punya kelas
        if (!$student || $student->status !== 'active' || !$student->class_id) {
            return response()->json(['error' => 'Anda tidak terdaftar di kelas aktif manapun.'], 403);
        }

        $materials = Material::with(['schedule.subject', 'schedule.teacher.user'])
            ->whereHas('schedule', function ($query) use ($student) {
                // Filter materi berdasarkan kelas siswa
                $query->where('class_id', $student->class_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($materials);
    }

    public function download(string $id): StreamedResponse|JsonResponse
    {
        $student = auth('api')->user()->student;
        $material = Material::with('schedule')->findOrFail($id);

        // Otorisasi ketat: Mencegah URL spoofing (Siswa A download materi Kelas B)
        if ($material->schedule->class_id !== $student->class_id) {
            return response()->json(['error' => 'Akses ditolak: Materi ini bukan untuk kelas Anda.'], 403);
        }

        if (!Storage::disk('public')->exists($material->file_path)) {
            return response()->json(['error' => 'File fisik tidak ditemukan di server.'], 404);
        }

        // Return file sebagai response download
        return Storage::disk('public')->download($material->file_path, $material->title);
    }
}