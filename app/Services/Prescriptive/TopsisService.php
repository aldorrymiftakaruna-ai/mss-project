<?php

namespace App\Services\Prescriptive;

use App\Models\AhpCriterion;
use App\Models\AhpSession;
use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Models\TopsisResult;
use App\Services\MtbfService;
use Illuminate\Support\Facades\DB;

class TopsisService
{
    /**
     * Menghitung ranking TOPSIS untuk sesi AHP tertentu.
     *
     * Equipment yang tidak memiliki data CM sama sekali (cm_findings = 0, avg_severity = 0,
     * cm_status = 0, cm_alarm_danger_count = 0) dipisahkan dari perhitungan ranking
     * karena nilai 0 untuk cost criteria akan membuat mereka mendapat skor terlalu baik.
     *
     * Asset tanpa data ditampilkan terpisah sebagai "Belum Ada Data Monitoring".
     *
     * NOTE: Ranking diurutkan ASCENDING berdasarkan score. Asset dengan skor terkecil
     * (paling berbahaya/dekat ke ideal negatif) mendapat ranking #1 — prioritas tertinggi
     * untuk ditindaklanjuti.
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
                'session'             => ['id' => $session->id, 'name' => $session->name],
                'criteria'            => $criteria,
                'weights'             => $weights,
                'matrix'              => [],
                'normalized'          => [],
                'rankings'            => [],
                'assets_no_data'      => [],
                'message'             => 'Tidak ada data asset untuk diproses.',
            ];
        }

        $criterionNames = $criteria->pluck('name')->toArray();

        // Pisahkan asset berdasarkan kelengkapan data CM
        // Asset tanpa CM: semua kriteria CM bernilai 0
        $cmCriteriaNames = ['cm_findings', 'avg_severity', 'cm_status', 'cm_alarm_danger_count'];
        $hasCmCriteria   = !empty(array_intersect($cmCriteriaNames, $criterionNames));

        $assetsWithData = [];   // asset yang punya data CM
        $assetsNoData   = [];   // asset tanpa data CM sama sekali

        foreach ($decisionData as $assetId => $data) {
            if ($hasCmCriteria) {
                $cmFields = array_intersect_key($data, array_flip($cmCriteriaNames));
                $cmFields = array_filter($cmFields, fn($v) => $v > 0);

                // Jika semua field CM bernilai 0 -> tidak punya data CM
                if (empty($cmFields)) {
                    $assetsNoData[] = $assetId;
                    continue;
                }
            }
            $assetsWithData[] = $assetId;
        }

        // Filter asset dalam sesi ini: hanya asset dengan data yang di-ranking
        if (!empty($assetsWithData)) {
            $matrix = [];
            $filteredIds = array_flip($assetsWithData);
            foreach ($decisionData as $assetId => $data) {
                if (!isset($filteredIds[$assetId])) {
                    continue;
                }
                $row = [];
                foreach ($criterionNames as $name) {
                    $row[$name] = $data[$name] ?? 0;
                }
                $matrix[$assetId] = $row;
            }

            // 1. Normalisasi
            $normalized = $this->normalize($matrix, $criterionNames);

            // 2. Terapkan bobot
            $weighted = $this->applyWeights($normalized, $weights, $criterionNames);

            // 3. Solusi ideal
            $ideal = $this->idealSolution($weighted, $criterionNames, $criteria);

            // 4. Jarak & ranking
            $rankings = $this->calculateRankingFromIdeal($weighted, $ideal, $assetsWithData);
        } else {
            $matrix     = [];
            $normalized = [];
            $weighted   = [];
            $ideal      = ['positive' => [], 'negative' => []];
            $rankings   = [];
        }

        // Hapus semua hasil TOPSIS lama untuk sesi ini (biar ranking konsisten)
        TopsisResult::where('ahp_session_id', $session->id)->delete();

        $assetModels = Asset::whereIn('id', $assetsWithData)->get()->keyBy('id');

        $rankingResult = [];
        foreach ($rankings as $item) {
            $asset = $assetModels->get($item['asset_id']);

            // Simpan ke database hanya untuk asset dengan data
            TopsisResult::create([
                'ahp_session_id' => $session->id,
                'asset_id'       => $item['asset_id'],
                'score'          => round($item['score'], 6),
                'd_plus'         => round($item['d_plus'], 6),
                'd_minus'        => round($item['d_minus'], 6),
                'ranking'        => $item['ranking'],
                'calculated_at'  => now(),
            ]);

            $rankingResult[] = [
                'asset_id'          => $item['asset_id'],
                'tag_no'            => $asset ? $asset->tag_no : '—',
                'description'       => $asset ? $asset->description : '—',
                'distance_positive' => round($item['d_plus'], 4),
                'distance_negative' => round($item['d_minus'], 4),
                'score'             => round($item['score'], 4),
                'ranking'           => $item['ranking'],
            ];
        }

        // Siapkan data asset tanpa CM
        $assetNoDataModels = Asset::whereIn('id', $assetsNoData)->get();
        $assetsNoDataList  = [];
        foreach ($assetNoDataModels as $asset) {
            $assetsNoDataList[] = [
                'asset_id'    => $asset->id,
                'tag_no'      => $asset->tag_no,
                'description' => $asset->description,
            ];
        }

        return [
            'session'        => [
                'id'   => $session->id,
                'name' => $session->name,
            ],
            'criteria'       => $criteria,
            'weights'        => $weights,
            'matrix'         => $matrix,
            'normalized'     => $normalized,
            'weighted'       => $weighted,
            'ideal'          => $ideal,
            'rankings'       => $rankingResult,
            'assets_no_data' => $assetsNoDataList,
            'message'        => null,
        ];
    }

    /**
     * Setiap asset dievaluasi berdasarkan kriteria yang diambil dari:
     * - Jumlah temuan CM (semakin sedikit semakin baik -> cost)
     * - Rata-rata severity CM (semakin rendah semakin baik -> cost)
     * - Status terakhir CM: 0=normal, 1=alarm, 2=danger (cost)
     * - Frekuensi measurement berstatus alarm/danger (cost)
     * - Total downtime (semakin sedikit semakin baik -> cost)
     * - Jumlah laporan maintenance (semakin banyak -> benefit)
     * - MTBF (semakin tinggi semakin baik -> benefit)
     *
     * @return array [assetId => [kriteriaName => value]]
     */
    public function buildDecisionMatrix(): array
    {
        $assets = Asset::with([
            'cmMeasurements',
            'cmFindings',
        ])->withCount([
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

            // ── Status terakhir dari CM measurement ────────────────
            $latestMeasurement = $asset->cmMeasurements->sortByDesc('tanggal')->first();
            $cmStatus = 0; // default normal
            if ($latestMeasurement) {
                $calcStatus = $asset->calcStatusFromCm($latestMeasurement);
                $cmStatus = match ($calcStatus) {
                    'danger' => 2,
                    'alarm'  => 1,
                    default  => 0,
                };
            }

            // ── Frekuensi measurement yang alarm/danger ────────────
            $alarmDangerCount = 0;
            foreach ($asset->cmMeasurements as $m) {
                $s = $asset->calcStatusFromCm($m);
                if ($s === 'alarm' || $s === 'danger') {
                    $alarmDangerCount++;
                }
            }

            // Total downtime
            $totalDowntime = (float) MaintenanceReport::where('asset_id', $asset->id)
                ->whereNotNull('downtime_minutes')
                ->sum('downtime_minutes');

            // MTBF (Mean Time Between Failures) dalam hari
            $mtbfDays = app(MtbfService::class)->hitungNumerik($asset);

            $matrix[$asset->id] = [
                'cm_findings'          => (float) $asset->cmFindings->count(),
                'avg_severity'         => (float) ($avgSeverity ?? 0),
                'cm_status'            => (float) $cmStatus,
                'cm_alarm_danger_count' => (float) $alarmDangerCount,
                'downtime'             => $totalDowntime,
                'report_count'         => (float) ($asset->report_count ?? 0),
                'mtbf_days'            => $mtbfDays,
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
     * Benefit criteria -> ideal positif = max, ideal negatif = min
     * Cost criteria -> ideal positif = min, ideal negatif = max
     *
     * Cost criteria (semakin rendah semakin baik / makin kecil nilainya makin bagus):
     *   cm_findings, avg_severity, cm_status, cm_alarm_danger_count, downtime
     * Benefit criteria (semakin tinggi semakin baik):
     *   report_count, mtbf_days
     *
     * Asset dengan nilai cost tinggi akan punya D+ besar (jauh dari ideal positif)
     * dan skor kecil → ranking #1 (prioritas perbaikan).
     *
     * @param array $weighted
     * @param array $criterionNames
     * @param \Illuminate\Support\Collection $criteria
     * @return array
     */
    public function idealSolution(array $weighted, array $criterionNames, $criteria): array
    {
        // Tentukan arah kriteria berdasarkan nama
        $costCriteria    = ['cm_findings', 'avg_severity', 'cm_status', 'cm_alarm_danger_count', 'downtime'];
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
     * NOTE: Karena semua kriteria bersifat cost (semakin rendah semakin baik),
     * asset dengan score rendah (dekat ke solusi ideal) justru perlu prioritas.
     * Urutan ranking: score terendah = ranking #1 (prioritas tertinggi).
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

            $dPlus  = 0;
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

        // Urutkan berdasarkan score ASCENDING — score rendah = prioritas tinggi
        // Karena asset dengan nilai cost criteria tinggi akan punya skor kecil
        // (jauh dari ideal positif), dan inilah yang butuh prioritas perbaikan.
        usort($results, fn($a, $b) => $a['score'] <=> $b['score']);

        // Beri ranking (1 = prioritas tertinggi)
        $ranking = 1;
        foreach ($results as &$item) {
            $item['ranking'] = $ranking++;
        }

        return $results;
    }
}
