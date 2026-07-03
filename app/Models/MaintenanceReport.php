<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceReport extends Model
{
    protected $fillable = [
        'asset_id',
        'reported_by',
        'shift',
        'tanggal',
        'jenis',
        'deskripsi_masalah',
        'tindakan',
        'report_code',
        'work_duration_minutes',
        'root_cause',
        'photo_documentation',
        'wizard_started_at',
        'submitted_at',
        'ai_suggestion_json',
        'status',
        'foto_path',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'photo_documentation' => 'array',
        'ai_suggestion_json' => 'array',
        'wizard_started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'work_duration_minutes' => 'integer',
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