<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index on submissions.student_id for bulk lookups (homeroomRecap, calculateWeightedAverage)
        Schema::table('submissions', function (Blueprint $table) {
            $table->index('student_id', 'idx_submissions_student_id');
        });

        // Index on attendances.student_id for bulk lookups (calculateWeightedAverage attendance query)
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('student_id', 'idx_attendances_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_student_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_student_id');
        });
    }
};
