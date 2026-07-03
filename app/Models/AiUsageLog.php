<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiUsageLog extends Model
{
    protected $fillable = [
        'provider_id',
        'report_id',
        'tokens_used',
        'request_type',
        'response_time_ms',
        'status',
        'error_message',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(MaintenanceReport::class, 'report_id');
    }

    /**
     * Filter log dalam rentang N jam terakhir.
     */
    public function scopeLastHours(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    public function scopeSuccess(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeError(Builder $query): Builder
    {
        return $query->where('status', 'error');
    }

    /**
     * Ringkasan statistik per request_type dalam N jam terakhir.
     */
    public static function statsByRequestType(int $hours = 24): Collection
    {
        return static::query()
            ->lastHours($hours)
            ->select([
                'request_type',
                DB::raw('COUNT(*) as total_calls'),
                DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count"),
                DB::raw("SUM(CASE WHEN status = 'error'   THEN 1 ELSE 0 END) as error_count"),
                DB::raw('ROUND(AVG(tokens_used), 0) as avg_tokens'),
                DB::raw('SUM(tokens_used) as total_tokens'),
                DB::raw('ROUND(AVG(response_time_ms), 0) as avg_response_ms'),
            ])
            ->groupBy('request_type')
            ->orderByDesc('total_calls')
            ->get();
    }

    /**
     * Statistik penggunaan token harian per provider dalam N hari terakhir.
     */
    public static function dailyTokensPerProvider(int $days = 7): Collection
    {
        return static::query()
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->select([
                'provider_id',
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw('SUM(tokens_used) as total_tokens'),
                DB::raw('COUNT(*) as total_calls'),
            ])
            ->groupBy('provider_id', DB::raw('DATE(created_at)'))
            ->orderBy('tanggal')
            ->get();
    }

    /**
     * Statistik ringkas per provider dalam 24 jam terakhir.
     */
    public static function statsPerProvider24h(): Collection
    {
        return static::query()
            ->lastHours(24)
            ->select([
                'provider_id',
                DB::raw('COUNT(*) as total_calls'),
                DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count"),
                DB::raw("SUM(CASE WHEN status = 'error'   THEN 1 ELSE 0 END) as error_count"),
                DB::raw('SUM(tokens_used) as total_tokens'),
                DB::raw('ROUND(AVG(response_time_ms), 0) as avg_response_ms'),
            ])
            ->groupBy('provider_id')
            ->get()
            ->keyBy('provider_id');
    }
}