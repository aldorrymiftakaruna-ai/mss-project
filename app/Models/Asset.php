<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'company_id', 'tag_no', 'name', 'model',
        'brand', 'serial_number', 'type', 'status', 'description'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function spareParts()
    {
        return $this->belongsToMany(SparePart::class, 'asset_spare_parts')
                    ->withPivot('jumlah_kebutuhan', 'keterangan')
                    ->withTimestamps();
    }

    public function maintenanceReports()
    {
        return $this->hasMany(MaintenanceReport::class);
    }

    public function cmMeasurements()
    {
        return $this->hasMany(CmMeasurement::class);
    }

    public function cmFindings()
    {
        return $this->hasMany(CmFinding::class);
    }
}