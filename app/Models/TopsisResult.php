<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopsisResult extends Model
{
    protected $fillable = [
        'ahp_session_id',
        'asset_id',
        'score',
        'ranking',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:6',
            'ranking' => 'integer',
            'calculated_at' => 'datetime',
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
     * Relasi ke asset.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
