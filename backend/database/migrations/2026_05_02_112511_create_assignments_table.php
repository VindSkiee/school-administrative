<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('file_path')->nullable(); // File pendukung tugas (opsional)
            $table->dateTime('due_date');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('assignments');
    }
};