<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceReport extends Model
{
    protected $fillable = [
        'asset_id', 'reported_by', 'shift', 'tanggal',
        'jenis', 'deskripsi_masalah', 'tindakan',
        'status', 'foto_path', 'catatan'
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reported_by');
    }

    public function manpowerLogs()
    {
        return $this->hasMany(ManpowerLog::class);
    }
}