<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeibullResult extends Model
{
    protected $fillable = [
        'asset_id',
        'beta',
        'eta',
        'mttf',
        'reliability_at_period',
        'parameters_json',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'beta' => 'decimal:6',
            'eta' => 'decimal:2',
            'mttf' => 'decimal:2',
            'reliability_at_period' => 'decimal:5',
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
