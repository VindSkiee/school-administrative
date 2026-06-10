<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Tambahkan kolom tanggal
            $table->date('date')->after('schedule_id')->nullable();
            
            // Tambahkan kolom attachments untuk menyimpan array JSON
            $table->json('attachments')->after('description')->nullable();
            
            // Hapus kolom file_path lama karena sudah tidak dipakai (opsional, tapi disarankan)
            if (Schema::hasColumn('materials', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['date', 'attachments']);
            $table->string('file_path')->nullable();
        });
    }
};