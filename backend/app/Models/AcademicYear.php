<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\RecordsActivity;

class AcademicYear extends Model
{
    use RecordsActivity;
    protected $fillable = ['name', 'semester', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}