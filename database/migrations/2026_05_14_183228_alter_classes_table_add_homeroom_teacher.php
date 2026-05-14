<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('homeroom_teacher_id')
                  ->nullable()
                  ->after('academic_year_id')
                  ->constrained('teachers', 'user_id')
                  ->nullOnDelete(); // Jika guru dihapus, kelas tidak ikut terhapus, status wali kelas jadi null
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['homeroom_teacher_id']);
            $table->dropColumn('homeroom_teacher_id');
        });
    }
};