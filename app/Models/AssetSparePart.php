<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetSparePart extends Model
{
    protected $fillable = [
        'asset_id', 'spare_part_id', 'jumlah_kebutuhan', 'keterangan'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }
}