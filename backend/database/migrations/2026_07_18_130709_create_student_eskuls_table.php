<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_eskuls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')->references('user_id')->on('students')->cascadeOnDelete();
            $table->foreignId('eskul_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->foreign('graded_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'eskul_id', 'academic_year_id']);
            $table->index('student_id');
            $table->index('eskul_id');
            $table->index('academic_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_eskuls');
    }
};
