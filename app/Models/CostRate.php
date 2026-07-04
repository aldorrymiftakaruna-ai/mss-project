<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostRate extends Model
{
    protected $fillable = [
        'company_id',
        'downtime_rate_per_min',
        'overtime_rate_per_hour',
        'effective_date',
    ];

    protected function casts(): array
    {
        return [
            'downtime_rate_per_min' => 'decimal:2',
            'overtime_rate_per_hour' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    /**
     * Relasi ke perusahaan.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
