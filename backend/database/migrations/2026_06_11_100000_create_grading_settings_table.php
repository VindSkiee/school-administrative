<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grading_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->integer('task_weight');
            $table->integer('uts_weight');
            $table->integer('uas_weight');
            $table->timestamps();

            $table->unique('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_settings');
    }
};
