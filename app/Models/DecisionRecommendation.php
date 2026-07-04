<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionRecommendation extends Model
{
    protected $fillable = [
        'asset_id',
        'recommendation_type',
        'priority_score',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'priority_score' => 'decimal:3',
        ];
    }

    /**
     * Relasi ke asset.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
