<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Tambahkan kolom date untuk tanggal pertemuan
            $table->date('date')->after('schedule_id')->nullable();
            
            // Tambahkan kolom attachments (JSON) untuk banyak file
            $table->json('attachments')->after('due_date')->nullable();
            
            // Hapus kolom file_path lama
            if (Schema::hasColumn('assignments', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['date', 'attachments']);
            $table->string('file_path')->nullable();
        });
    }
};