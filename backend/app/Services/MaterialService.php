<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MaterialService
{
    public function uploadMaterial(int $teacherId, array $data, array $files): \App\Models\Material
    {
        $schedule = \App\Models\Schedule::query()->findOrFail($data['schedule_id']);

        if ($schedule->teacher_id !== $teacherId) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, "Akses ditolak: Anda tidak mengajar di jadwal ini.");
        }

        $paths = [];
        foreach ($files as $file) {
            // Simpan setiap file dan masukkan path-nya ke array
            $paths[] = $file->store('materials', 'public');
        }
        
        // Simpan array path ke dalam kolom JSON 'attachments'
        $data['attachments'] = $paths;
        
        return \App\Models\Material::query()->create($data);
    }

    public function deleteMaterial(int $teacherId, Material $material): void
    {
        $schedule = Schedule::query()->findOrFail($material->schedule_id);
        
        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, "Akses ditolak: Anda tidak memiliki hak untuk menghapus materi ini.");
        }

        // Hapus file fisik dari storage
        if (Storage::disk('public')->exists($material->file_path)) {
            Storage::disk('public')->delete($material->file_path);
        }

        // Menggunakan method destroy bawaan model agar Intelephense tidak protes
        // atau bisa menggunakan $material->query()->delete();
        Material::destroy($material->id);
    }
}