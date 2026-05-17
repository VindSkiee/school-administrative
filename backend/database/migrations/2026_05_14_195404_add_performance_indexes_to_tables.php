<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Index untuk tabel attendances (Sangat sering di-query per jadwal dan tanggal)
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['schedule_id', 'date']);
            $table->index('status');
        });

        // Index untuk tabel submissions (Sering difilter berdasarkan tugas dan siswa)
        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['assignment_id', 'student_id']);
        });

        // Index untuk tabel schedules (Pencarian jadwal per kelas atau per guru)
        Schema::table('schedules', function (Blueprint $table) {
            $table->index('class_id');
            $table->index('teacher_id');
            $table->index('day_of_week');
        });

        // Index untuk tabel classes (Pencarian kelas per tahun ajaran)
        Schema::table('classes', function (Blueprint $table) {
            $table->index('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['schedule_id', 'date']);
            $table->dropIndex(['status']);
        });
        // ... drop index lainnya untuk rollback
    }
};