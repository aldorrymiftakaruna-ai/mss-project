<?php

namespace App\Services\Predictive;

use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Models\WeibullResult;
use Illuminate\Support\Collection;

class WeibullService
{
    /**
     * Estimasi parameter Weibull (beta, eta) menggunakan Median Rank Regression (MRR).
     *
     * Asumsi: data time-to-failure (TTF) dalam hari dari laporan maintenance.
     *
     * @param Asset $asset
     * @return array ['beta', 'eta', 'mttf', 'reliability_at_period', 'message']
     */
    public function estimateForAsset(Asset $asset): array
    {
        $ttf = $this->getTimeToFailure($asset);

        if ($ttf->count() < 2) {
            return [
                'beta'                  => null,
                'eta'                   => null,
                'mttf'                  => null,
                'reliability_at_period' => null,
                'message'               => 'Data tidak cukup (min. 3 laporan maintenance diperlukan untuk 2 titik TTF).',
            ];
        }

        try {
            $values = $ttf->sort()->values()->toArray();
            $n = count($values);

            // Median Rank (Bernard's approximation)
            $x = array_map(function ($v) {
                return log($v);
            }, $values);

            $y = [];
            for ($i = 1; $i <= $n; $i++) {
                $medianRank = ($i - 0.3) / ($n + 0.4);
                $y[] = log(-log(1 - $medianRank));
            }

            // Linear regression: y = m*x + c
            $sumX = array_sum($x);
            $sumY = array_sum($y);
            $sumXY = 0;
            $sumX2 = 0;

            for ($i = 0; $i < $n; $i++) {
                $sumXY += $x[$i] * $y[$i];
                $sumX2 += $x[$i] * $x[$i];
            }

            $m = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
            $c = ($sumY - $m * $sumX) / $n;

            // beta = slope (m)
            $beta = $m;

            // eta = exp(-c / m)
            $eta = exp(-$c / $m);

            // MTTF = eta * Gamma(1 + 1/beta)
            $mttf = $eta * $this->gamma(1 + 1 / $beta);

            // Reliability pada periode tertentu (contoh: 30 hari)
            $period = 30;
            $reliability = exp(-pow($period / $eta, $beta));

            $result = [
                'beta'                  => round($beta, 4),
                'eta'                   => round($eta, 2),
                'mttf'                  => round($mttf, 2),
                'reliability_at_period' => round($reliability, 5),
                'message'               => null,
            ];

            // Simpan ke database
            $this->persistResult($asset->id, $result);

            return $result;
        } catch (\Exception $e) {
            return [
                'beta'                  => null,
                'eta'                   => null,
                'mttf'                  => null,
                'reliability_at_period' => null,
                'message'               => 'Gagal menghitung: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mendapatkan Time-To-Failure (TTF) untuk suatu asset.
     *
     * TTF = selisih hari antara laporan maintenance berurutan.
     *
     * @param Asset $asset
     * @return Collection
     */
    public function getTimeToFailure(Asset $asset): Collection
    {
        $reports = MaintenanceReport::where('asset_id', $asset->id)
            ->whereNotNull('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('tanggal');

        if ($reports->count() < 2) {
            return collect();
        }

        $ttf = collect();
        $prev = null;

        foreach ($reports as $date) {
            if ($prev !== null) {
                $diff = $prev->diffInDays($date);
                if ($diff > 0) {
                    $ttf->push($diff);
                }
            }
            $prev = $date;
        }

        return $ttf;
    }

    /**
     * Fungsi Gamma (Γ) menggunakan pendekatan Lanczos.
     *
     * @param float $z
     * @return float
     */
    public function gamma(float $z): float
    {
        // Lanczos approximation
        $g = 7;
        $p = [
            0.99999999999980993,
            676.5203681218851,
            -1259.1392167224028,
            771.32342877765313,
            -176.61502916214059,
            12.507343278686905,
            -0.13857109526572012,
            9.9843695780195716e-6,
            1.5056327351493116e-7,
        ];

        if ($z < 0.5) {
            return M_PI / (sin(M_PI * $z) * $this->gamma(1 - $z));
        }

        $z -= 1;
        $x = $p[0];
        for ($i = 1; $i < $g + 2; $i++) {
            $x += $p[$i] / ($z + $i);
        }

        $t = $z + $g + 0.5;
        return sqrt(2 * M_PI) * pow($t, $z + 0.5) * exp(-$t) * $x;
    }

    /**
     * Menyimpan hasil ke tabel weibull_results.
     *
     * @param int $assetId
     * @param array $result
     * @return WeibullResult
     */
    public function persistResult(int $assetId, array $result): WeibullResult
    {
        return WeibullResult::updateOrCreate(
            ['asset_id' => $assetId],
            [
                'beta'                  => $result['beta'],
                'eta'                   => $result['eta'],
                'mttf'                  => $result['mttf'],
                'reliability_at_period' => $result['reliability_at_period'],
                'calculated_at'         => now(),
            ]
        );
    }

    /**
     * Hitung Weibull untuk semua asset.
     *
     * @return array ['processed' => int, 'skipped' => int]
     */
    public function estimateAll(): array
    {
        $assets = Asset::all();
        $processed = 0;
        $skipped = 0;

        foreach ($assets as $asset) {
            $result = $this->estimateForAsset($asset);
            if ($result['beta'] !== null) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        return compact('processed', 'skipped');
    }
}
