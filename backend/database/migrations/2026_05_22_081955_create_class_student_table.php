<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();

            // 1. Definisikan kolom manual untuk student_id
            $table->unsignedBigInteger('student_id');
            // Referensikan ke kolom 'user_id' di tabel 'students'
            $table->foreign('student_id')
                ->references('user_id')
                ->on('students')
                ->cascadeOnDelete();

            // 2. Untuk class dan academic_year tetap bisa pakai constrained()
            // karena tabel mereka memang memiliki Primary Key bernama 'id'
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            // 3. Mencegah duplikasi data (1 siswa tidak bisa masuk kelas yang sama 2x)
            $table->unique(['student_id', 'class_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student');
    }
};
