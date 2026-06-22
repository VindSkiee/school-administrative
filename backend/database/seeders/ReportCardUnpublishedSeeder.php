<?php

namespace Database\Seeders;

/**
 * Identical to ReportCardScenarioSeeder but with is_report_published = false.
 * Use this seeder when you need a full dataset for development/testing
 * where the semester report has NOT been published yet (all CRUD operations remain unlocked).
 *
 * Usage: php artisan db:seed --class=ReportCardUnpublishedSeeder
 */
class ReportCardUnpublishedSeeder extends ReportCardScenarioSeeder
{
    protected bool $reportPublished = false;

    public function run(): void
    {
        $this->command->warn('⚠️  Memulai ReportCardUnpublishedSeeder — data lama akan dihapus...');
        $this->command->info('   📝 Mode: is_report_published = false (semua CRUD aktif)');

        // Call parent logic
        parent::run();
    }
}
