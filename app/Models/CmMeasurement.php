<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmMeasurement extends Model
{
    protected $fillable = [
    'asset_id', 'measured_by', 'tanggal',
    'driver_de_vib_v', 'driver_de_vib_h', 'driver_de_vib_a', 'driver_de_cf', 'driver_de_temp',
    'driver_nde_vib_v', 'driver_nde_vib_h', 'driver_nde_vib_a', 'driver_nde_cf', 'driver_nde_temp',
    'driver_ampere',
    'driven_de_vib_v', 'driven_de_vib_h', 'driven_de_vib_a', 'driven_de_cf', 'driven_de_temp',
    'driven_nde_vib_v', 'driven_nde_vib_h', 'driven_nde_vib_a', 'driven_nde_cf', 'driven_nde_temp',
    'catatan'
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