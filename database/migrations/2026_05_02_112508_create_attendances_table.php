<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students', 'user_id')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'sick', 'permission', 'late']);
            $table->timestamps();
            
            $table->unique(['schedule_id', 'student_id', 'date']);
        });
    }

    public function down() {
        Schema::dropIfExists('attendances');
    }
};