<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove duplicate schedules before adding the unique constraint.
        // Keep the row with the lowest id for each (class_id, day_of_week, start_time, academic_year_id) group.
        $duplicates = DB::select("
            SELECT class_id, day_of_week, start_time, academic_year_id, MIN(id) as keep_id
            FROM schedules
            GROUP BY class_id, day_of_week, start_time, academic_year_id
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $dup) {
            // Delete all rows in this group EXCEPT the one with the lowest id
            DB::table('schedules')
                ->where('class_id', $dup->class_id)
                ->where('day_of_week', $dup->day_of_week)
                ->where('start_time', $dup->start_time)
                ->where('academic_year_id', $dup->academic_year_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        // Add unique constraint: no two schedules for the same class at the same day+time in the same academic year
        Schema::table('schedules', function (Blueprint $table) {
            $table->unique(
                ['class_id', 'day_of_week', 'start_time', 'academic_year_id'],
                'unique_class_schedule'
            );
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique('unique_class_schedule');
        });
    }
};
