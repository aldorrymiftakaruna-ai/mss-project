<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotUnknownAsset extends Model
{
    protected $table = 'bot_unknown_assets';

    protected $fillable = [
        'report_id',
        'keyword_mentioned',
    ];

    /**
     * Relasi ke laporan maintenance (bukan Report).
     */
    public function maintenanceReport(): BelongsTo
    {
        return $this->belongsTo(MaintenanceReport::class, 'report_id');
    }
}