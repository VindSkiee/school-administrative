<?php

namespace App\Models;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicYear extends Model
{
    use RecordsActivity;

    protected $fillable = ['name', 'semester', 'is_active', 'is_report_published', 'start_date', 'end_date'];

    protected $casts = ['is_active' => 'boolean', 'is_report_published' => 'boolean', 'start_date' => 'date', 'end_date' => 'date'];

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
}
