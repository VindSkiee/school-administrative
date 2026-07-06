<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = [
        'schedule_id',
        'type',
        'date',
        'title',
        'description',
        'due_date',
        'attachments',
        'is_remedial',
        'remedial_for_type',
        'linked_assignment_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'attachments' => 'array',
        'is_remedial' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function parentAssignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'linked_assignment_id');
    }

    public function remedialAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'linked_assignment_id');
    }
}
