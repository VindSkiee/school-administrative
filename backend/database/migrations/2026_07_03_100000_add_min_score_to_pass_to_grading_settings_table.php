<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_settings', function (Blueprint $table) {
            $table->integer('min_score_to_pass')->default(60)->after('attendance_weight');
        });
    }

    public function down(): void
    {
        Schema::table('grading_settings', function (Blueprint $table) {
            $table->dropColumn('min_score_to_pass');
        });
    }
};
