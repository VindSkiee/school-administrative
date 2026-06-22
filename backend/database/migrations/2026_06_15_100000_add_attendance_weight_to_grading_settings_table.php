<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grading_settings', function (Blueprint $table) {
            $table->integer('attendance_weight')->default(10)->after('uas_weight');
        });
    }

    public function down(): void
    {
        Schema::table('grading_settings', function (Blueprint $table) {
            $table->dropColumn('attendance_weight');
        });
    }
};
