<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'label', 'unit', 'group'];

    /**
     * Ambil nilai setting langsung dari DB — tanpa cache.
     * Sederhana dan tidak ada masalah serialization.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function clearCache(): void
    {
        // Tidak ada cache — tidak perlu dibersihkan
    }
}