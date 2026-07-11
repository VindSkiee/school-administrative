<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('meeting_session_id')->references('id')->on('meeting_sessions')->cascadeOnDelete();
            $table->index('meeting_session_id');
        });

        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->foreign('meeting_session_id')->references('id')->on('meeting_sessions')->cascadeOnDelete();
            $table->index('meeting_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['meeting_session_id']);
            $table->dropIndex(['meeting_session_id']);
        });

        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->dropForeign(['meeting_session_id']);
            $table->dropIndex(['meeting_session_id']);
        });
    }
};
