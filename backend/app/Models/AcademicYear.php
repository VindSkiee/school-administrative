<?php

namespace App\Models;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

class AcademicYear extends Model
{
    use RecordsActivity;

    public const CACHE_KEY = 'active_academic_year';

    /**
     * Get the active academic year (cached).
     */
    public static function active(): ?self
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function () {
            return self::where('is_active', true)->first();
        });
    }

    /**
     * Flush the active academic year cache. Call after setActive().
     */
    public static function flushActiveCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected $fillable = ['name', 'semester', 'phase', 'is_active', 'is_report_published', 'start_date', 'end_date', 'eskul_registration_deadline'];

    protected $casts = ['is_active' => 'boolean', 'is_report_published' => 'boolean', 'start_date' => 'date', 'end_date' => 'date', 'eskul_registration_deadline' => 'date'];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function gradingSetting(): HasOne
    {
        return $this->hasOne(GradingSetting::class);
    }

    public function studentEskuls(): HasMany
    {
        return $this->hasMany(StudentEskul::class);
    }
}
