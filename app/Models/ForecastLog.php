<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastLog extends Model
{
    protected $fillable = [
        'model_type',
        'period',
        'actual_value',
        'forecast_value',
        'absolute_error',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'actual_value' => 'decimal:2',
            'forecast_value' => 'decimal:2',
            'absolute_error' => 'decimal:2',
            'calculated_at' => 'datetime',
        ];
    }
}
