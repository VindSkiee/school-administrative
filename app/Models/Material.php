<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    protected $fillable = ['schedule_id', 'title', 'description', 'file_path'];

    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
}