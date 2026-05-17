<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2); // Mendukung nilai desimal, e.g., 95.50
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('teachers', 'user_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('grades');
    }
};