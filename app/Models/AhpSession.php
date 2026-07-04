<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AhpSession extends Model
{
    protected $fillable = [
        'name',
        'ahli_id',
        'consistency_ratio',
        'is_final',
    ];

    protected function casts(): array
    {
        return [
            'consistency_ratio' => 'decimal:5',
            'is_final' => 'boolean',
        ];
    }

    /**
     * Relasi ke ahli (employee).
     */
    public function ahli(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'ahli_id');
    }

    /**
     * Relasi ke kriteria AHP.
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(AhpCriterion::class, 'ahp_session_id');
    }

    /**
     * Relasi ke pairwise comparisons.
     */
    public function pairwise(): HasMany
    {
        return $this->hasMany(AhpPairwise::class, 'ahp_session_id');
    }

    /**
     * Relasi ke hasil TOPSIS.
     */
    public function topsisResults(): HasMany
    {
        return $this->hasMany(TopsisResult::class, 'ahp_session_id');
    }
}
