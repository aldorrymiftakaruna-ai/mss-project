<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    protected $fillable = [
        'kode_material', 'deskripsi', 'satuan',
        'stok_minimum', 'stok_tersedia', 'kategori'
    ];

    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'asset_spare_parts')
                    ->withPivot('jumlah_kebutuhan', 'keterangan')
                    ->withTimestamps();
    }

    public function stocks()
    {
        return $this->hasMany(SparePartStock::class);
    }

    public function getStatusAttribute()
    {
        if ($this->stok_tersedia == 0) return 'habis';
        if ($this->stok_tersedia < $this->stok_minimum) return 'kritis';
        return 'aman';
    }
}