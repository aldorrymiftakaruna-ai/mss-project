<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparePartImage extends Model
{
    protected $fillable = ['spare_part_id', 'path', 'label'];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}