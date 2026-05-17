<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\AttendanceRequest;
use App\Models\Material;
use App\Models\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOrphanedFiles extends Command
{
    /**
     * Nama command untuk dijalankan di terminal
     */
    protected $signature = 'storage:clean-orphans';

    /**
     * Deskripsi command
     */
    protected $description = 'Menghapus file fisik di storage yang sudah tidak memiliki relasi di database.';

    public function handle()
    {
        $this->info('Memulai proses pembersihan file (Orphaned Files)...');

        $disk = Storage::disk('public');
        $totalDeleted = 0;

        // Daftar folder yang akan dipindai beserta model dan kolom yang mereferensikannya
        $directories = [
            'materials' => [Material::class, 'file_path'],
            'assignments' => [Assignment::class, 'file_path'],
            'submissions' => [Submission::class, 'file_path'],
            'attendance_proofs' => [AttendanceRequest::class, 'proof_file_path'],
        ];

        foreach ($directories as $folder => $config) {
            $modelClass = $config[0];
            $column = $config[1];

            $this->line("Memindai direktori: <comment>{$folder}</comment>");

            // 1. Ambil semua file fisik di folder tersebut
            $physicalFiles = $disk->files($folder);

            // 2. Ambil semua path yang tercatat di database
            $dbFiles = $modelClass::query()
                ->whereNotNull($column)
                ->pluck($column)
                ->toArray();

            // 3. Cari selisihnya (File fisik yang tidak ada di DB)
            $orphanedFiles = array_diff($physicalFiles, $dbFiles);

            if (empty($orphanedFiles)) {
                $this->info(" -> Tidak ada file yang tidak memiliki relasi di {$folder}.");

                continue;
            }

            // 4. Hapus file yang tidak memiliki relasi
            foreach ($orphanedFiles as $orphan) {
                $disk->delete($orphan);
                $this->line(" -> Terhapus: {$orphan}");
                $totalDeleted++;
            }
        }

        $this->info("Selesai! Total file sampah yang dibersihkan: {$totalDeleted}");
    }
}
