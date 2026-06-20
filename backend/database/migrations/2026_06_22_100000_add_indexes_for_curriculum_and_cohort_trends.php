<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes required by Principal Dashboard
 * Curriculum Trend and Cohort Trend endpoints.
 *
 * These indexes support:
 *   - Demographic aggregation: GROUP BY academic_year_id on class_student
 *   - Cohort identification: correlated subquery on class_student(student_id)
 *   - Score aggregation: JOIN grades on submission_id with WHERE score IS NOT NULL
 *   - Score grouping: GROUP BY assignments(schedule_id, type)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── class_student: demographics + cohort identification ──
        // Covers: WHERE academic_year_id = ? GROUP BY class_id (Curriculum demographics)
        Schema::table('class_student', function (Blueprint $table) {
            $table->index(
                ['academic_year_id', 'class_id'],
                'idx_class_student_ay_class'
            );
        });

        // Covers: WHERE student_id = ? ORDER BY academic_year_id (Cohort MIN subquery)
        // Also covers: WHERE student_id IN (...) (Cohort score join)
        Schema::table('class_student', function (Blueprint $table) {
            $table->index(
                ['student_id', 'academic_year_id'],
                'idx_class_student_student_ay'
            );
        });

        // ── grades: score aggregation for both endpoints ──
        // Covers: JOIN grades ON submission_id WHERE score IS NOT NULL + AVG(score)
        // The composite index allows index-only scan for AVG(score) queries
        Schema::table('grades', function (Blueprint $table) {
            $table->index(
                ['submission_id', 'score'],
                'idx_grades_submission_score'
            );
        });

        // ── assignments: score grouping by schedule + type ──
        // Covers: GROUP BY schedule_id, type in curriculum/cohort score queries
        Schema::table('assignments', function (Blueprint $table) {
            $table->index(
                ['schedule_id', 'type'],
                'idx_assignments_schedule_type'
            );
        });
    }

    public function down(): void
    {
        Schema::table('class_student', function (Blueprint $table) {
            $table->dropIndex('idx_class_student_ay_class');
            $table->dropIndex('idx_class_student_student_ay');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('idx_grades_submission_score');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_schedule_type');
        });
    }
};
