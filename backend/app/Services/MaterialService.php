<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Schedule;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MaterialService
{
    public function uploadMaterial(int $teacherId, array $data, array $files): Material
    {
        $schedule = Schedule::query()->findOrFail($data['schedule_id']);

        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak: Anda tidak mengajar di jadwal ini.');
        }

        $paths = [];
        foreach ($files as $file) {
            // Simpan setiap file dan masukkan path-nya ke array
            $paths[] = $file->store('materials', 'public');
        }

        // Simpan array path ke dalam kolom JSON 'attachments'
        $data['attachments'] = $paths;

        return Material::query()->create($data);
    }

    public function deleteMaterial(int $teacherId, Material $material): void
    {
        $schedule = Schedule::query()->findOrFail($material->schedule_id);

        if ($schedule->teacher_id !== $teacherId) {
            throw new HttpException(403, 'Akses ditolak: Anda tidak memiliki hak untuk menghapus materi ini.');
        }

        // Hapus semua file fisik dari storage (attachments array + file_path legacy)
        $filesToDelete = [];
        if ($material->file_path) {
            $filesToDelete[] = $material->file_path;
        }
        if (is_array($material->attachments)) {
            $filesToDelete = array_merge($filesToDelete, $material->attachments);
        }
        foreach (array_unique($filesToDelete) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        Material::destroy($material->id);
    }
}
