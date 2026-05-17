<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students', 'user_id')->cascadeOnDelete();
            $table->string('file_path');
            $table->dateTime('submitted_at');
            $table->timestamps();
            
            // Satu siswa hanya bisa submit satu kali per tugas (bisa diupdate file-nya nanti)
            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down() {
        Schema::dropIfExists('submissions');
    }
};