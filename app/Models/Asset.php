<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'company_id', 'tag_no', 'description', 'model',
        'serial_number', 'head_capacity', 'motor_kw', 'motor_rpm',
        'motor_ampere', 'status',
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
        return $this->hasMany(MaintenanceReport::class)->latest('tanggal');
    }

    public function cmMeasurements()
    {
        return $this->hasMany(CmMeasurement::class)->latest('tanggal');
    }

    public function cmFindings()
    {
        return $this->hasMany(CmFinding::class)->latest('tanggal');
    }

    public function riskScores()
    {
        return $this->hasMany(RiskScore::class);
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'alarm'  => 'bg-amber-100 text-amber-700',
            'danger' => 'bg-red-100 text-red-700',
            default  => 'bg-green-100 text-green-700',
        };
    }

    private function loadThresholds(): array
    {
        $kw = $this->motor_kw ?? 0;

        if ($kw <= 15) {
            $alarm  = (float) Setting::get('vib_class1_alarm',  2.3);
            $danger = (float) Setting::get('vib_class1_danger', 4.5);
        } elseif ($kw <= 75) {
            $alarm  = (float) Setting::get('vib_class2_alarm',  4.5);
            $danger = (float) Setting::get('vib_class2_danger', 7.1);
        } elseif ($kw <= 300) {
            $alarm  = (float) Setting::get('vib_class3_alarm',  7.1);
            $danger = (float) Setting::get('vib_class3_danger', 11.0);
        } else {
            $alarm  = (float) Setting::get('vib_class4_alarm',  11.0);
            $danger = (float) Setting::get('vib_class4_danger', 18.0);
        }

        $tempDanger = (float) Setting::get('temp_danger', 82);

        return compact('alarm', 'danger', 'tempDanger');
    }

    public function calcStatusFromCm(CmMeasurement $cm): string
    {
        ['alarm' => $vibAlarm, 'danger' => $vibDanger, 'tempDanger' => $tempDanger]
            = $this->loadThresholds();

        $vibFields = [
            'driver_de_vib_v',  'driver_de_vib_h',  'driver_de_vib_a',
            'driver_nde_vib_v', 'driver_nde_vib_h', 'driver_nde_vib_a',
            'driven_de_vib_v',  'driven_de_vib_h',  'driven_de_vib_a',
            'driven_nde_vib_v', 'driven_nde_vib_h', 'driven_nde_vib_a',
        ];

        $maxVib = 0;
        foreach ($vibFields as $f) {
            $val = (float) ($cm->$f ?? 0);
            if ($val > $maxVib) $maxVib = $val;
        }

        $tempFields = [
            'driver_de_temp', 'driver_nde_temp',
            'driven_de_temp', 'driven_nde_temp',
        ];

        $maxTemp = 0;
        foreach ($tempFields as $f) {
            $val = (float) ($cm->$f ?? 0);
            if ($val > $maxTemp) $maxTemp = $val;
        }

        $vibStatus  = $maxVib  >= $vibDanger  ? 'danger' : ($maxVib  >= $vibAlarm  ? 'alarm' : 'normal');
        $tempStatus = $maxTemp >= $tempDanger  ? 'danger' : 'normal';

        $rank = ['normal' => 0, 'alarm' => 1, 'danger' => 2];
        return $rank[$vibStatus] >= $rank[$tempStatus] ? $vibStatus : $tempStatus;
    }

    public function refreshStatus(): void
    {
        $latest = $this->cmMeasurements()->latest('tanggal')->first();

        if (!$latest) {
            $this->update(['status' => 'normal']);
            return;
        }

        $this->update(['status' => $this->calcStatusFromCm($latest)]);
    }

    public function thresholds(): array
    {
        return $this->loadThresholds();
    }
}