<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmFinding extends Model
{
    protected $fillable = [
        'asset_id', 'reported_by', 'tanggal',
        'kategori', 'deskripsi', 'severity', 'status', 'foto_path'
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
}