<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = ['schedule_id', 'title', 'description', 'file_path', 'due_date'];
    protected $casts = ['due_date' => 'datetime'];

    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function submissions(): HasMany { return $this->hasMany(Submission::class); }
}