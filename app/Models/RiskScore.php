<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskScore extends Model
{
    protected $fillable = [
        'asset_id',
        'score',
        'category',
        'parameters_json',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:3',
            'category' => 'string',
            'parameters_json' => 'array',
            'calculated_at' => 'datetime',
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
