<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Filter class + year (most common in ScheduleController index + gradebook)
            $table->index(['class_id', 'academic_year_id'], 'idx_schedules_class_year');

            // Filter teacher + year (TeacherGradebookController, TeacherDashboard)
            $table->index(['teacher_id', 'academic_year_id'], 'idx_schedules_teacher_year');

            // Clash detection: year + day + time range
            // Column order enables index range scan on start_time/end_time
            $table->index(
                ['academic_year_id', 'day_of_week', 'start_time', 'end_time'],
                'idx_schedules_clash_detection'
            );

            // ORDER BY day_of_week, start_time (ScheduleController index, dashboard today)
            $table->index(['day_of_week', 'start_time'], 'idx_schedules_order');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_class_year');
            $table->dropIndex('idx_schedules_teacher_year');
            $table->dropIndex('idx_schedules_clash_detection');
            $table->dropIndex('idx_schedules_order');
        });
    }
};
