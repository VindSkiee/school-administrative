<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\RecordsActivity;

class AcademicYear extends Model
{
    use RecordsActivity;
    protected $fillable = ['name', 'semester', 'is_active', 'is_report_published'];
    protected $casts = ['is_active' => 'boolean', 'is_report_published' => 'boolean'];

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