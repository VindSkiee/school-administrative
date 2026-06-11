<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $student = $user->student()->with('classes')->first();

        // Validasi Status & Kelas Siswa
        if (!$student || strtolower($student->status) !== 'active') {
            return response()->json(['error' => 'Akun siswa tidak aktif.'], 403);
        }

        $activeClass = $student->classes->first();
        if (!$activeClass) {
            return response()->json(['error' => 'Anda tidak terdaftar di kelas aktif manapun.'], 403);
        }

        $query = Material::with(['schedule.subject', 'schedule.teacher.user'])
            ->whereHas('schedule', function ($q) use ($activeClass) {
                $q->where('class_id', $activeClass->id); // Ganti ke ID dari tabel pivot
            });

        // Fitur FILTER PENCARIAN (Judul / Deskripsi)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->schedule_id);
        }

        $materials = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($materials);
    }

    public function download(Request $request, string $id)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $student = $user->student()->with('classes')->first();
        $activeClass = $student->classes->first();

        $material = Material::with('schedule')->findOrFail($id);

        if (!$activeClass || $material->schedule->class_id !== $activeClass->id) {
            return response()->json(['error' => 'Akses ditolak: Materi ini bukan untuk kelas Anda.'], 403);
        }

        // Tangkap nama file spesifik dari Frontend
        $filePath = $request->query('file');

        // Pastikan materi memiliki attachments, dan file yang direquest ada di dalam array tersebut
        $attachments = is_string($material->attachments) ? json_decode($material->attachments, true) : $material->attachments;

        if (!$filePath || !is_array($attachments) || !in_array($filePath, $attachments)) {
            return response()->json(['error' => 'File tidak valid atau tidak ditemukan dalam materi ini.'], 404);
        }

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json(['error' => 'File fisik tidak ditemukan di server.'], 404);
        }

        // PERBAIKAN: Ambil nama file dan ekstensinya (misal: 'materi123.pdf')
        $fileName = basename($filePath);

        // Tambahkan nama file sebagai parameter kedua agar header Content-Disposition valid
        return Storage::disk('public')->download($filePath, $fileName);
    }
}