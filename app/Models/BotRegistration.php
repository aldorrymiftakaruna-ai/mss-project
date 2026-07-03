<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotRegistration extends Model
{
    protected $table = 'bot_registrations';

    protected $fillable = [
        'telegram_id',
        'telegram_username',
        'name',
        'nik',
        'requested_at',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Relasi ke employee yang memproses pendaftaran.
     * Menggunakan model Employee (bukan Technician).
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'processed_by');
    }
}