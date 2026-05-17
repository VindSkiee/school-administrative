<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: "2025/2026"
            $table->enum('semester', ['odd', 'even']);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('academic_years');
    }
};
