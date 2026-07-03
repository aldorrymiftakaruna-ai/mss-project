<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManpowerLog extends Model
{
    protected $fillable = [
        'maintenance_report_id', 'employee_id', 'durasi_menit'
    ];

    public function maintenanceReport()
    {
        return $this->belongsTo(MaintenanceReport::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}