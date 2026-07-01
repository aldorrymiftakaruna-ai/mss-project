<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmFinding extends Model
{
    protected $fillable = [
        'asset_id', 'tanggal', 'kategori', 'severity', 'status',
        'analysis', 'action', 'pic', 'date_action', 'remark',
        'foto_path', 'foto_path_2', 'foto_path_3', 'finding_code',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'date_action' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}