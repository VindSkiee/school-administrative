<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    // 1. Pastikan date dan attachments masuk ke fillable
    protected $fillable = [
        'schedule_id',
        'date',
        'title',
        'description',
        'attachments',
    ];

    // 2. Beri tahu Laravel bahwa attachments adalah JSON (Array)
    protected $casts = [
        'attachments' => 'array',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}