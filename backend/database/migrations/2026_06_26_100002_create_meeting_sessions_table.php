<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('meeting_number');
            $table->date('date');
            $table->enum('status', ['scheduled', 'completed', 'holiday', 'canceled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'meeting_number']);
            $table->index(['schedule_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_sessions');
    }
};
