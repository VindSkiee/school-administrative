<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subject_competency_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            // Sangat Baik
            $table->integer('sangat_baik_min')->default(85);
            $table->text('sangat_baik_text');

            // Baik
            $table->integer('baik_min')->default(75);
            $table->text('baik_text');

            // Kurang
            $table->integer('kurang_min')->default(60);
            $table->text('kurang_text');

            // Sangat Kurang
            $table->integer('sangat_kurang_min')->default(0);
            $table->text('sangat_kurang_text');

            $table->timestamps();
            $table->unique(['subject_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_competency_settings');
    }
};
