<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add index on students.status for frequent active-status filtering.
     *
     * Covers queries like:
     *   - Student::where('status', 'active')
     *   - GradeAggregationService::getClassAggregate() → where('students.status', 'active')
     *   - AttendanceService::storeBulkAttendance() → whereHas('classes', ...) with status filter
     *   - PrincipalDashboardService → Student::where('status', 'active')->count()
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('status', 'idx_students_status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_status');
        });
    }
};
