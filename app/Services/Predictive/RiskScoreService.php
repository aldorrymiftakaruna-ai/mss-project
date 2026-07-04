<?php

namespace App\Services\Predictive;

use App\Models\Asset;
use App\Models\CmFinding;
use App\Models\CmMeasurement;
use App\Models\RiskScore;
use Illuminate\Support\Collection;

class RiskScoreService
{
    /**
     * Hitung risk score untuk satu asset berdasarkan data CM.
     *
     * @param Asset $asset
     * @return array [score, category, parameters, trend_data]
     */
    public function calculateForAsset(Asset $asset): array
    {
        $measurements = $this->getRecentMeasurements($asset);

        if ($measurements->isEmpty()) {
            return [
                'score'       => 0,
                'category'    => 'rendah',
                'parameters'  => [],
                'trend_data'  => [],
            ];
        }

        // 1. Hitung slope tren vibrasi & temperatur
        $vibSlope  = $this->calculateTrendSlope($measurements, 'vibration');
        $tempSlope = $this->calculateTrendSlope($measurements, 'temperature');

        // 2. Hitung level terkini vs threshold
        $vibLevel = $this->calculateCurrentLevel($measurements, 'vibration', $asset);
        $tempLevel = $this->calculateCurrentLevel($measurements, 'temperature', $asset);

        // 3. Hitung rate of change (acceleration)
        $roc = $this->calculateRateOfChange($measurements);

        // 4. Composite score
        $weights = config('risk-score.weights');
        $score = 0;
        $parameters = [];

        // Normalisasi slope: bagi dengan max slope umum (10 mm/s per bulan)
        $normVibSlope  = $this->normalizeSlope($vibSlope, 5.0);
        $normTempSlope = $this->normalizeSlope($tempSlope, 15.0);
        $normVibLevel  = min($vibLevel / 1.0, 1.0);
        $normTempLevel = min($tempLevel / 1.0, 1.0);
        $normRoc       = $this->normalizeSlope($roc, 3.0);

        $parameters = [
            'vibration_slope'     => round($vibSlope, 4),
            'vibration_slope_norm'=> round($normVibSlope, 4),
            'temperature_slope'   => round($tempSlope, 4),
            'temperature_slope_norm'=> round($normTempSlope, 4),
            'vibration_level'     => round($vibLevel, 4),
            'vibration_level_norm'=> round($normVibLevel, 4),
            'temperature_level'   => round($tempLevel, 4),
            'temperature_level_norm'=> round($normTempLevel, 4),
            'rate_of_change'      => round($roc, 4),
            'rate_of_change_norm' => round($normRoc, 4),
        ];

        $score = (
            $weights['vibration_slope']   * $normVibSlope +
            $weights['temperature_slope'] * $normTempSlope +
            $weights['vibration_level']   * $normVibLevel +
            $weights['temperature_level'] * $normTempLevel +
            $weights['rate_of_change']    * $normRoc
        );

        // 5. Penalty untuk temuan CM severity tinggi
        $hasHighFinding = CmFinding::where('asset_id', $asset->id)
            ->whereIn('severity', ['high', 'critical'])
            ->where('status', '!=', 'closed')
            ->exists();

        if ($hasHighFinding) {
            $score *= config('risk-score.finding_penalty', 1.2);
        }

        $score = min($score, 1.0);

        // 6. Kategori
        $category = $this->classifyRisk($score);

        return [
            'score'      => round($score, 4),
            'category'   => $category,
            'parameters' => $parameters,
        ];
    }

    /**
     * Ambil N titik data CM terakhir untuk asset.
     *
     * @param Asset $asset
     * @return Collection
     */
    public function getRecentMeasurements(Asset $asset): Collection
    {
        $n = config('risk-score.n_points', 8);

        return CmMeasurement::where('asset_id', $asset->id)
            ->whereNotNull('tanggal')
            ->orderBy('tanggal', 'desc')
            ->take($n)
            ->get()
            ->sortBy('tanggal')
            ->values();
    }

    /**
     * Hitung slope tren menggunakan linear regression (least squares).
     *
     * Data diambil dari kolom vibrasi (12 field) atau temperatur (4 field).
     * Nilai representatif per pengukuran = rata-rata dari semua field.
     *
     * @param Collection $measurements
     * @param string $type 'vibration' | 'temperature'
     * @return float Slope (perubahan per unit waktu dalam hari)
     */
    public function calculateTrendSlope(Collection $measurements, string $type): float
    {
        if ($measurements->count() < 2) {
            return 0;
        }

        $vibFields = [
            'driver_de_vib_v', 'driver_de_vib_h', 'driver_de_vib_a',
            'driver_nde_vib_v', 'driver_nde_vib_h', 'driver_nde_vib_a',
            'driven_de_vib_v', 'driven_de_vib_h', 'driven_de_vib_a',
            'driven_nde_vib_v', 'driven_nde_vib_h', 'driven_nde_vib_a',
        ];

        $tempFields = [
            'driver_de_temp', 'driver_nde_temp',
            'driven_de_temp', 'driven_nde_temp',
        ];

        $fields = $type === 'vibration' ? $vibFields : $tempFields;

        // Siapkan data points (x = hari dari data pertama, y = rata-rata nilai)
        $points = [];
        $baseDate = $measurements->first()->tanggal;

        foreach ($measurements as $m) {
            $x = $baseDate->diffInDays($m->tanggal);
            $values = [];
            foreach ($fields as $f) {
                if ($m->$f !== null) {
                    $values[] = (float) $m->$f;
                }
            }
            if (empty($values)) continue;
            $y = array_sum($values) / count($values);
            $points[] = ['x' => $x, 'y' => $y];
        }

        if (count($points) < 2) {
            return 0;
        }

        return $this->linearRegressionSlope($points);
    }

    /**
     * Hitung slope linear regression dengan metode least squares.
     *
     * @param array $points [['x' => float, 'y' => float], ...]
     * @return float
     */
    public function linearRegressionSlope(array $points): float
    {
        $n = count($points);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($points as $p) {
            $sumX  += $p['x'];
            $sumY  += $p['y'];
            $sumXY += $p['x'] * $p['y'];
            $sumX2 += $p['x'] * $p['x'];
        }

        $denominator = $n * $sumX2 - $sumX * $sumX;
        if ($denominator == 0) {
            return 0;
        }

        return ($n * $sumXY - $sumX * $sumY) / $denominator;
    }

    /**
     * Hitung level terkini (latest) sebagai rasio terhadap threshold alarm.
     *
     * @param Collection $measurements
     * @param string $type 'vibration' | 'temperature'
     * @param Asset $asset
     * @return float 0-1 (1 = sudah mencapai threshold alarm)
     */
    public function calculateCurrentLevel(Collection $measurements, string $type, Asset $asset): float
    {
        $latest = $measurements->last();
        if (!$latest) return 0;

        $thresholds = $asset->thresholds();
        $alarmThreshold = $type === 'vibration' ? $thresholds['alarm'] : $thresholds['tempDanger'];

        if ($alarmThreshold <= 0) return 0;

        $fields = $type === 'vibration'
            ? ['driver_de_vib_v', 'driver_de_vib_h', 'driver_de_vib_a',
               'driver_nde_vib_v', 'driver_nde_vib_h', 'driver_nde_vib_a',
               'driven_de_vib_v', 'driven_de_vib_h', 'driven_de_vib_a',
               'driven_nde_vib_v', 'driven_nde_vib_h', 'driven_nde_vib_a']
            : ['driver_de_temp', 'driver_nde_temp', 'driven_de_temp', 'driven_nde_temp'];

        $maxVal = 0;
        foreach ($fields as $f) {
            $val = (float) ($latest->$f ?? 0);
            if ($val > $maxVal) $maxVal = $val;
        }

        return $alarmThreshold > 0 ? min($maxVal / $alarmThreshold, 1.0) : 0;
    }

    /**
     * Hitung rate of change (perubahan slope antar subset data).
     *
     * Membagi data menjadi 2 subset (awal dan akhir) dan hitung selisih slopenya.
     *
     * @param Collection $measurements
     * @return float
     */
    public function calculateRateOfChange(Collection $measurements): float
    {
        if ($measurements->count() < 4) return 0;

        $mid = (int) floor($measurements->count() / 2);
        $firstHalf = $measurements->slice(0, $mid);
        $secondHalf = $measurements->slice($mid);

        $slope1 = $this->calculateTrendSlope($firstHalf, 'vibration');
        $slope2 = $this->calculateTrendSlope($secondHalf, 'vibration');

        return abs($slope2 - $slope1);
    }

    /**
     * Normalisasi slope agar berada di rentang 0-1.
     *
     * @param float $slope
     * @param float $maxSlope Slope maksimum yang dianggap wajar
     * @return float
     */
    public function normalizeSlope(float $slope, float $maxSlope): float
    {
        $absSlope = abs($slope);
        if ($maxSlope <= 0) return 0;
        return min($absSlope / $maxSlope, 1.0);
    }

    /**
     * Klasifikasikan skor ke dalam kategori risiko.
     *
     * @param float $score 0-1
     * @return string
     */
    public function classifyRisk(float $score): string
    {
        $thresholds = config('risk-score.thresholds');

        if ($score < $thresholds['rendah']) return 'rendah';
        if ($score < $thresholds['sedang']) return 'sedang';
        return 'tinggi';
    }

    /**
     * Hitung & simpan risk score untuk semua asset.
     *
     * @return array ['processed' => int, 'errors' => int]
     */
    public function calculateAll(): array
    {
        $assets = Asset::all();
        $processed = 0;
        $errors = 0;

        foreach ($assets as $asset) {
            try {
                $result = $this->calculateForAsset($asset);

                RiskScore::updateOrCreate(
                    ['asset_id' => $asset->id],
                    [
                        'score'           => $result['score'],
                        'category'        => $result['category'],
                        'parameters_json' => $result['parameters'],
                        'calculated_at'   => now(),
                    ]
                );
                $processed++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return compact('processed', 'errors');
    }

    /**
     * Ambil data tren untuk grafik (per titik ukur).
     *
     * @param Asset $asset
     * @param int $limit
     * @return array
     */
    public function getTrendChartData(Asset $asset, int $limit = 20): array
    {
        $measurements = CmMeasurement::where('asset_id', $asset->id)
            ->whereNotNull('tanggal')
            ->orderBy('tanggal', 'asc')
            ->take($limit)
            ->get();

        $labels = [];
        $driverDeVibV = [];
        $driverNdeVibV = [];
        $drivenDeVibV = [];
        $drivenNdeVibV = [];
        $driverDeTemp = [];
        $drivenDeTemp = [];

        foreach ($measurements as $m) {
            $labels[] = $m->tanggal ? $m->tanggal->format('d/m/Y') : '—';
            $driverDeVibV[] = (float) ($m->driver_de_vib_v ?? 0);
            $driverNdeVibV[] = (float) ($m->driver_nde_vib_v ?? 0);
            $drivenDeVibV[] = (float) ($m->driven_de_vib_v ?? 0);
            $drivenNdeVibV[] = (float) ($m->driven_nde_vib_v ?? 0);
            $driverDeTemp[] = (float) ($m->driver_de_temp ?? 0);
            $drivenDeTemp[] = (float) ($m->driven_de_temp ?? 0);
        }

        return compact(
            'labels',
            'driverDeVibV', 'driverNdeVibV',
            'drivenDeVibV', 'drivenNdeVibV',
            'driverDeTemp', 'drivenDeTemp'
        );
    }
}
