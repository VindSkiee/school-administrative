<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_competency_settings', function (Blueprint $table) {
            $table->integer('min_score')->default(60)->after('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::table('subject_competency_settings', function (Blueprint $table) {
            $table->dropColumn('min_score');
        });
    }
};
