<?php

namespace App\Services\Prescriptive;

use App\Models\AhpCriterion;
use App\Models\AhpSession;
use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Models\TopsisResult;
use Illuminate\Support\Facades\DB;

class TopsisService
{
    /**
     * Menghitung ranking TOPSIS untuk sesi AHP tertentu.
     *
     * Langkah:
     * 1. Bangun decision matrix dari data real (cm_findings, maintenance_reports)
     * 2. Normalisasi matriks
     * 3. Terapkan bobot AHP
     * 4. Tentukan solusi ideal positif & negatif
     * 5. Hitung jarak ke solusi ideal
     * 6. Hitung closeness coefficient & ranking
     *
     * @param AhpSession $session
     * @return array
     */
    public function calculateRanking(AhpSession $session): array
    {
        $criteria = $session->criteria()->orderBy('id')->get();
        $weights  = $criteria->pluck('weight', 'name')->toArray();

        $decisionData = $this->buildDecisionMatrix();

        if (empty($decisionData)) {
            return [
                'session'    => ['id' => $session->id, 'name' => $session->name],
                'criteria'   => $criteria,
                'weights'    => $weights,
                'matrix'     => [],
                'normalized' => [],
                'rankings'   => [],
                'message'    => 'Tidak ada data asset untuk diproses.',
            ];
        }

        // Ambil hanya kolom kriteria berdasarkan nama dari bobot
        $criterionNames = $criteria->pluck('name')->toArray();

        $matrix = [];
        $assetKeys = [];
        foreach ($decisionData as $assetId => $data) {
            $row = [];
            foreach ($criterionNames as $name) {
                $row[$name] = $data[$name] ?? 0;
            }
            $matrix[$assetId] = $row;
            $assetKeys[] = $assetId;
        }

        // 1. Normalisasi
        $normalized = $this->normalize($matrix, $criterionNames);

        // 2. Terapkan bobot
        $weighted = $this->applyWeights($normalized, $weights, $criterionNames);

        // 3. Solusi ideal
        $ideal = $this->idealSolution($weighted, $criterionNames, $criteria);

        // 4. Jarak & ranking
        $rankings = $this->calculateRankingFromIdeal($weighted, $ideal, $assetKeys);

        $assetModels = Asset::whereIn('id', $assetKeys)->get()->keyBy('id');

        $rankingResult = [];
        foreach ($rankings as $item) {
            $asset = $assetModels->get($item['asset_id']);

            // Simpan ke database
            TopsisResult::updateOrCreate(
                [
                    'ahp_session_id' => $session->id,
                    'asset_id'       => $item['asset_id'],
                ],
                [
                    'score'          => round($item['score'], 6),
                    'd_plus'         => round($item['d_plus'], 6),
                    'd_minus'        => round($item['d_minus'], 6),
                    'ranking'        => $item['ranking'],
                    'calculated_at'  => now(),
                ]
            );

            $rankingResult[] = [
                'asset_id'          => $item['asset_id'],
                'tag_no'            => $asset ? $asset->tag_no : '—',
                'description'       => $asset ? $asset->description : '—',
                'distance_positive' => round($item['d_plus'], 6),
                'distance_negative' => round($item['d_minus'], 6),
                'score'             => round($item['score'], 6),
                'ranking'           => $item['ranking'],
            ];
        }

        return [
            'session'    => [
                'id'   => $session->id,
                'name' => $session->name,
            ],
            'criteria'   => $criteria,
            'weights'    => $weights,
            'matrix'     => $matrix,
            'normalized' => $normalized,
            'weighted'   => $weighted,
            'ideal'      => $ideal,
            'rankings'   => $rankingResult,
            'message'    => null,
        ];
    }

    /**
     * Bangun decision matrix dari data real.
     *
     * Setiap asset dievaluasi berdasarkan kriteria yang diambil dari:
     * - Jumlah temuan CM (semakin sedikit semakin baik → cost)
     * - Rata-rata severity CM (semakin rendah semakin baik → cost)
     * - Total downtime (semakin sedikit semakin baik → cost)
     * - Jumlah laporan maintenance (semakin banyak → benefit — menunjukkan proaktif)
     * - MTBF (semakin tinggi semakin baik → benefit)
     *
     * @return array [assetId => [kriteriaName => value]]
     */
    public function buildDecisionMatrix(): array
    {
        $assets = Asset::withCount([
            'cmFindings as cm_findings_count',
            'maintenanceReports as report_count',
        ])->get();

        $matrix = [];

        foreach ($assets as $asset) {
            // Rata-rata severity CM (numeric)
            $avgSeverity = DB::table('cm_findings')
                ->where('asset_id', $asset->id)
                ->whereNotNull('severity')
                ->avg(DB::raw("CASE
                    WHEN severity = 'low' THEN 1
                    WHEN severity = 'medium' THEN 2
                    WHEN severity = 'high' THEN 3
                    WHEN severity = 'critical' THEN 4
                    ELSE 0 END"));

            // Total downtime
            $totalDowntime = (float) MaintenanceReport::where('asset_id', $asset->id)
                ->whereNotNull('downtime_minutes')
                ->sum('downtime_minutes');

            // MTBF (Mean Time Between Failures) dalam hari
            $reportDates = MaintenanceReport::where('asset_id', $asset->id)
                ->whereNotNull('created_at')
                ->orderBy('created_at')
                ->pluck('created_at');

            $mtbfDays = 0;
            if ($reportDates->count() >= 2) {
                $totalInterval = 0;
                $count = 0;
                $prev = null;
                foreach ($reportDates as $date) {
                    if ($prev !== null) {
                        $totalInterval += $prev->diffInDays($date);
                        $count++;
                    }
                    $prev = $date;
                }
                $mtbfDays = $count > 0 ? round($totalInterval / $count, 2) : 0;
            }

            $matrix[$asset->id] = [
                'cm_findings'  => (float) ($asset->cm_findings_count ?? 0),
                'avg_severity' => (float) ($avgSeverity ?? 0),
                'downtime'     => $totalDowntime,
                'report_count' => (float) ($asset->report_count ?? 0),
                'mtbf_days'    => $mtbfDays,
            ];
        }

        return $matrix;
    }

    /**
     * Normalisasi matriks keputusan menggunakan Euclidean norm.
     *
     * @param array $matrix [assetId => [kriteria => value]]
     * @param array $criterionNames
     * @return array
     */
    public function normalize(array $matrix, array $criterionNames): array
    {
        // Hitung denominator (sqrt dari sum of squares) per kolom
        $denominator = [];
        foreach ($criterionNames as $name) {
            $sumSquares = 0;
            foreach ($matrix as $row) {
                $sumSquares += ($row[$name] ?? 0) ** 2;
            }
            $denominator[$name] = sqrt($sumSquares) ?: 1; // hindari division by zero
        }

        $normalized = [];
        foreach ($matrix as $assetId => $row) {
            $normRow = [];
            foreach ($criterionNames as $name) {
                $normRow[$name] = ($row[$name] ?? 0) / $denominator[$name];
            }
            $normalized[$assetId] = $normRow;
        }

        return $normalized;
    }

    /**
     * Terapkan bobot AHP ke matriks ternormalisasi.
     *
     * @param array $normalized
     * @param array $weights [kriteriaName => weight]
     * @param array $criterionNames
     * @return array
     */
    public function applyWeights(array $normalized, array $weights, array $criterionNames): array
    {
        $weighted = [];
        foreach ($normalized as $assetId => $row) {
            $weightedRow = [];
            foreach ($criterionNames as $name) {
                $weightedRow[$name] = ($row[$name] ?? 0) * ($weights[$name] ?? 0);
            }
            $weighted[$assetId] = $weightedRow;
        }
        return $weighted;
    }

    /**
     * Tentukan solusi ideal positif (A+) dan negatif (A-).
     *
     * Benefit criteria → ideal positif = max, ideal negatif = min
     * Cost criteria → ideal positif = min, ideal negatif = max
     *
     * Default: cm_findings, avg_severity, downtime = cost (semakin rendah semakin baik)
     * report_count, mtbf_days = benefit (semakin tinggi semakin baik)
     *
     * @param array $weighted
     * @param array $criterionNames
     * @param \Illuminate\Support\Collection $criteria
     * @return array
     */
    public function idealSolution(array $weighted, array $criterionNames, $criteria): array
    {
        // Tentukan arah kriteria berdasarkan nama
        $costCriteria = ['cm_findings', 'avg_severity', 'downtime'];
        $benefitCriteria = ['report_count', 'mtbf_days'];

        $idealPositive = [];
        $idealNegative = [];

        foreach ($criterionNames as $name) {
            $values = array_column($weighted, $name);

            if (empty($values)) {
                $idealPositive[$name] = 0;
                $idealNegative[$name] = 0;
                continue;
            }

            $max = max($values);
            $min = min($values);

            if (in_array($name, $costCriteria)) {
                // Cost: ideal positif = min, ideal negatif = max
                $idealPositive[$name] = $min;
                $idealNegative[$name] = $max;
            } else {
                // Benefit: ideal positif = max, ideal negatif = min
                $idealPositive[$name] = $max;
                $idealNegative[$name] = $min;
            }
        }

        return [
            'positive' => $idealPositive,
            'negative' => $idealNegative,
        ];
    }

    /**
     * Hitung jarak Euclidean ke solusi ideal dan closeness coefficient.
     *
     * @param array $weighted
     * @param array $ideal
     * @param array $assetKeys
     * @return array [ [asset_id, d_plus, d_minus, score, ranking], ... ]
     */
    public function calculateRankingFromIdeal(array $weighted, array $ideal, array $assetKeys): array
    {
        $results = [];

        foreach ($assetKeys as $assetId) {
            $row = $weighted[$assetId] ?? [];

            $dPlus = 0;
            $dMinus = 0;

            foreach ($row as $name => $value) {
                $dPlus  += ($value - $ideal['positive'][$name]) ** 2;
                $dMinus += ($value - $ideal['negative'][$name]) ** 2;
            }

            $dPlus  = sqrt($dPlus);
            $dMinus = sqrt($dMinus);
            $score  = ($dPlus + $dMinus) > 0 ? $dMinus / ($dPlus + $dMinus) : 0;

            $results[] = [
                'asset_id' => $assetId,
                'd_plus'   => $dPlus,
                'd_minus'  => $dMinus,
                'score'    => $score,
            ];
        }

        // Urutkan berdasarkan score descending (semakin tinggi semakin baik)
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        // Beri ranking
        $ranking = 1;
        foreach ($results as &$item) {
            $item['ranking'] = $ranking++;
        }

        return $results;
    }
}
