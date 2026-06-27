<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmMeasurement extends Model
{
    protected $fillable = [
        'asset_id', 'measured_by', 'tanggal',
        'vibrasi_de', 'vibrasi_nde', 'temperature', 'pressure', 'catatan'
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function measuredBy()
    {
        return $this->belongsTo(Employee::class, 'measured_by');
    }
}