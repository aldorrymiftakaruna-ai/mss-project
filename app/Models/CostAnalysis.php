<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostAnalysis extends Model
{
    protected $fillable = [
        'maintenance_report_id',
        'downtime_cost',
        'overtime_cost',
        'labor_cost',
        'sparepart_cost',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'downtime_cost' => 'decimal:2',
            'overtime_cost' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'sparepart_cost' => 'decimal:2',
            'analyzed_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke laporan maintenance.
     */
    public function maintenanceReport(): BelongsTo
    {
        return $this->belongsTo(MaintenanceReport::class);
    }

    /**
     * Mendapatkan total biaya.
     */
    public function getTotalCostAttribute(): float
    {
        return (float) ($this->downtime_cost + $this->overtime_cost + $this->labor_cost + $this->sparepart_cost);
    }
}
