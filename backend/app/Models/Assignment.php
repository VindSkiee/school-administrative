<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    // 1. Tambahkan 'date' dan 'attachments', hapus 'file_path'
    protected $fillable = [
        'schedule_id',
        'date',
        'title',
        'description',
        'due_date',
        'attachments',
    ];

    // 2. Beri tahu Laravel tipe datanya
    protected $casts = [
        'due_date' => 'datetime',
        'attachments' => 'array', // Wajib agar JSON otomatis jadi Array
    ];

    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function submissions(): HasMany { return $this->hasMany(Submission::class); }
}