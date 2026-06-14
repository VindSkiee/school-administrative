<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectCompetencySetting extends Model
{
    protected $fillable = [
        'subject_id',
        'academic_year_id',
        'sangat_baik_min',
        'sangat_baik_text',
        'baik_min',
        'baik_text',
        'kurang_min',
        'kurang_text',
        'sangat_kurang_min',
        'sangat_kurang_text',
    ];

    protected $casts = [
        'sangat_baik_min' => 'integer',
        'baik_min' => 'integer',
        'kurang_min' => 'integer',
        'sangat_kurang_min' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Resolve the competency text for a given score.
     */
    public function resolveForScore(?float $score): string
    {
        if ($score === null) {
            return 'Belum ada data penilaian.';
        }

        if ($score >= $this->sangat_baik_min) {
            return $this->sangat_baik_text;
        }

        if ($score >= $this->baik_min) {
            return $this->baik_text;
        }

        if ($score >= $this->kurang_min) {
            return $this->kurang_text;
        }

        return $this->sangat_kurang_text;
    }
}
