<?php

namespace App\Services\Predictive;

use App\Models\ForecastLog;
use Illuminate\Support\Collection;

class ForecastService
{
    /**
     * Exponential Smoothing (ES) — simple exponential smoothing.
     * Ft+1 = α * Yt + (1-α) * Ft
     *
     * @param Collection $data Collection of objects with 'period' and 'value'
     * @param float $alpha Smoothing factor (0 < alpha < 1)
     * @param int $forecastCount Jumlah periode ke depan yang di-forecast
     * @return Collection
     */
    public function exponentialSmoothing(Collection $data, float $alpha, int $forecastCount = 1): Collection
    {
        if ($data->isEmpty()) {
            return collect();
        }

        $values = $data->pluck('value')->values();
        $periods = $data->pluck('period')->values();
        $n = $values->count();

        $results = collect();

        // Gunakan nilai pertama sebagai inisialisasi forecast (F1 = Y1)
        $forecast = (float) $values[0];

        // Simpan hasil untuk periode pertama (tanpa error karena tidak ada actual sebelumnya)
        $results->push((object) [
            'period' => $periods[0],
            'actual' => (float) $values[0],
            'forecast' => $forecast,
            'error' => 0,
            'absolute_error' => 0,
        ]);

        // Iterasi dari periode ke-2 sampai ke-n
        for ($i = 1; $i < $n; $i++) {
            $actual = (float) $values[$i];
            // Forecast untuk periode ini adalah dari perhitungan sebelumnya
            $currentForecast = $forecast;
            $error = $actual - $currentForecast;

            $results->push((object) [
                'period' => $periods[$i],
                'actual' => $actual,
                'forecast' => round($currentForecast, 2),
                'error' => round($error, 2),
                'absolute_error' => round(abs($error), 2),
            ]);

            // Update forecast untuk periode berikutnya
            $forecast = $alpha * $actual + (1 - $alpha) * $currentForecast;
        }

        // Forecast ke depan
        $lastPeriod = $periods->last();
        for ($j = 1; $j <= $forecastCount; $j++) {
            $nextPeriod = $this->incrementPeriod($lastPeriod, $j);
            $results->push((object) [
                'period' => $nextPeriod,
                'actual' => null,
                'forecast' => round($forecast, 2),
                'error' => null,
                'absolute_error' => null,
            ]);
        }

        return $results;
    }

    /**
     * Moving Average (MA).
     * Ft = (Yt-1 + Yt-2 + ... + Yt-n) / n
     *
     * @param Collection $data Collection of objects with 'period' and 'value'
     * @param int $period Jumlah periode dalam moving average (window size)
     * @param int $forecastCount Jumlah periode ke depan
     * @return Collection
     */
    public function movingAverage(Collection $data, int $period = 3, int $forecastCount = 1): Collection
    {
        if ($data->isEmpty() || $data->count() < $period) {
            return collect();
        }

        $values = $data->pluck('value')->values();
        $periods = $data->pluck('period')->values();
        $n = $values->count();

        $results = collect();

        // Periode awal (belum bisa di-forecast karena data belum cukup)
        for ($i = 0; $i < $period; $i++) {
            $results->push((object) [
                'period' => $periods[$i],
                'actual' => (float) $values[$i],
                'forecast' => null,
                'error' => null,
                'absolute_error' => null,
            ]);
        }

        // Hitung forecast untuk periode ke-period+1 sampai ke-n
        for ($i = $period; $i < $n; $i++) {
            $actual = (float) $values[$i];
            // Rata-rata dari 'period' data sebelumnya
            $window = array_slice($values->toArray(), $i - $period, $period);
            $forecast = array_sum($window) / $period;
            $error = $actual - $forecast;

            $results->push((object) [
                'period' => $periods[$i],
                'actual' => $actual,
                'forecast' => round($forecast, 2),
                'error' => round($error, 2),
                'absolute_error' => round(abs($error), 2),
            ]);
        }

        // Forecast ke depan: ambil rata-rata dari N data terakhir
        $lastWindow = array_slice($values->toArray(), -$period);
        $nextForecast = array_sum($lastWindow) / $period;

        $lastPeriod = $periods->last();
        for ($j = 1; $j <= $forecastCount; $j++) {
            $nextPeriod = $this->incrementPeriod($lastPeriod, $j);
            $results->push((object) [
                'period' => $nextPeriod,
                'actual' => null,
                'forecast' => round($nextForecast, 2),
                'error' => null,
                'absolute_error' => null,
            ]);
        }

        return $results;
    }

    /**
     * Menghitung error metrics MAE dan MSE dari hasil forecasting.
     *
     * @param Collection $results Hasil dari exponentialSmoothing atau movingAverage
     * @return array
     */
    public function errorMetrics(Collection $results): array
    {
        $errors = $results->filter(function ($r) {
            return $r->actual !== null && $r->forecast !== null;
        });

        if ($errors->isEmpty()) {
            return ['mae' => 0, 'mse' => 0, 'mape' => 0];
        }

        $count = $errors->count();
        $mae = $errors->sum('absolute_error') / $count;
        $mse = $errors->sum(function ($r) {
            return pow($r->error, 2);
        }) / $count;
        $mape = $errors->sum(function ($r) {
            return $r->actual != 0 ? abs($r->error / $r->actual) * 100 : 0;
        }) / $count;

        return [
            'mae' => round($mae, 2),
            'mse' => round($mse, 2),
            'mape' => round($mape, 2),
        ];
    }

    /**
     * Menyimpan hasil forecasting ke tabel forecast_logs.
     *
     * @param string $modelType 'ES' atau 'MA'
     * @param Collection $results
     * @return void
     */
    public function saveResults(string $modelType, Collection $results): void
    {
        foreach ($results as $r) {
            if ($r->actual !== null && $r->forecast !== null) {
                ForecastLog::create([
                    'model_type' => $modelType,
                    'period' => $r->period,
                    'actual_value' => $r->actual,
                    'forecast_value' => $r->forecast,
                    'absolute_error' => $r->absolute_error,
                    'calculated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Mendapatkan data historis downtime dari laporan maintenance per bulan.
     *
     * @param int $months Jumlah bulan ke belakang
     * @return Collection
     */
    public function getHistoricalDowntimeData(int $months = 12): Collection
    {
        $results = \Illuminate\Support\Facades\DB::table('maintenance_reports')
            ->select(
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as period"),
                \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(downtime_minutes), 0) as value')
            )
            ->whereNotNull('tanggal')
            ->where('tanggal', '>=', now()->subMonths($months))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $results;
    }

    /**
     * Mendapatkan data historis work_duration dari laporan maintenance per bulan.
     *
     * @param int $months Jumlah bulan ke belakang
     * @return Collection
     */
    public function getHistoricalWorkDurationData(int $months = 12): Collection
    {
        $results = \Illuminate\Support\Facades\DB::table('maintenance_reports')
            ->select(
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as period"),
                \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(work_duration_minutes), 0) as value')
            )
            ->whereNotNull('tanggal')
            ->where('tanggal', '>=', now()->subMonths($months))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $results;
    }

    /**
     * Increment period string (YYYY-MM) sebanyak N bulan.
     *
     * @param string $period
     * @param int $offset
     * @return string
     */
    private function incrementPeriod(string $period, int $offset = 1): string
    {
        $parts = explode('-', $period);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        $month += $offset;
        while ($month > 12) {
            $month -= 12;
            $year++;
        }

        return sprintf('%d-%02d', $year, $month);
    }
}
