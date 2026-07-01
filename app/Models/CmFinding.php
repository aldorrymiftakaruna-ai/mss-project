<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmFinding extends Model
{
    protected $fillable = [
        'asset_id', 'reported_by', 'tanggal',
        'kategori', 'deskripsi', 'severity', 'status', 'remark',
        'foto_path', 'foto_path_2', 'foto_path_3',
        'analysis', 'action', 'pic_id', 'date_action',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'date_action' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reported_by');
    }

    public function pic()
    {
        return $this->belongsTo(Employee::class, 'pic_id');
    }
}