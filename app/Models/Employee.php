<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'telegram_id',
        'telegram_username',
        'role',
        'shift',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function maintenanceReports()
    {
        return $this->hasMany(MaintenanceReport::class, 'reported_by');
    }

    public function manpowerLogs()
    {
        return $this->hasMany(ManpowerLog::class);
    }

    public function cmMeasurements()
    {
        return $this->hasMany(CmMeasurement::class, 'measured_by');
    }
}