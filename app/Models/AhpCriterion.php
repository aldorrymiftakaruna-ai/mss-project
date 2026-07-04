<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AhpCriterion extends Model
{
    protected $table = 'ahp_criteria';

    protected $fillable = [
        'ahp_session_id',
        'name',
        'label',
        'weight',
        'priority_vector',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:5',
            'priority_vector' => 'decimal:5',
        ];
    }

    /**
     * Relasi ke sesi AHP.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AhpSession::class, 'ahp_session_id');
    }

    /**
     * Relasi ke pairwise sebagai kriteria A.
     */
    public function pairwiseAsA(): HasMany
    {
        return $this->hasMany(AhpPairwise::class, 'criterion_a_id');
    }

    /**
     * Relasi ke pairwise sebagai kriteria B.
     */
    public function pairwiseAsB(): HasMany
    {
        return $this->hasMany(AhpPairwise::class, 'criterion_b_id');
    }
}
