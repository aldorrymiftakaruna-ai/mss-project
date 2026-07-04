<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AhpPairwise extends Model
{
    protected $table = 'ahp_pairwise';

    protected $fillable = [
        'ahp_session_id',
        'criterion_a_id',
        'criterion_b_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
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
     * Relasi ke kriteria A.
     */
    public function criterionA(): BelongsTo
    {
        return $this->belongsTo(AhpCriterion::class, 'criterion_a_id');
    }

    /**
     * Relasi ke kriteria B.
     */
    public function criterionB(): BelongsTo
    {
        return $this->belongsTo(AhpCriterion::class, 'criterion_b_id');
    }
}
