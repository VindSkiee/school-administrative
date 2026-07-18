<?php

namespace App\Models;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eskul extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'name',
        'description',
        'teacher_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id', 'id');
    }

    public function studentEskuls(): HasMany
    {
        return $this->hasMany(StudentEskul::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(StudentEskul::class);
    }
}
