<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eskul_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')->references('user_id')->on('students')->cascadeOnDelete();
            $table->foreignId('current_eskul_id')->constrained('eskuls')->cascadeOnDelete();
            $table->foreignId('requested_eskul_id')->constrained('eskuls')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->timestamps();

            $table->index('student_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eskul_change_requests');
    }
};
