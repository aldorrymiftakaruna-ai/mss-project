<?php

namespace App\Services\Prescriptive;

use App\Models\AhpSession;
use App\Models\Asset;
use App\Models\CostAnalysis;
use App\Models\DecisionRecommendation;
use App\Models\RiskScore;
use App\Models\TopsisResult;
use App\Services\Cost\MaintenanceCostService;
use App\Services\Predictive\RiskScoreService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrescriptiveEngine
{
    protected AhpService $ahpService;
    protected TopsisService $topsisService;
    protected RiskScoreService $riskScoreService;
    protected MaintenanceCostService $costService;

    public function __construct(
        AhpService $ahpService,
        TopsisService $topsisService,
        RiskScoreService $riskScoreService,
        MaintenanceCostService $costService
    ) {
        $this->ahpService = $ahpService;
        $this->topsisService = $topsisService;
        $this->riskScoreService = $riskScoreService;
        $this->costService = $costService;
    }

    /**
     * Mendapatkan data untuk bagian Predictive (Risk Score).
     *
     * @return array
     */
    public function getPredictiveData(): array
    {
        $riskScores = RiskScore::with('asset.company')
            ->whereNotNull('calculated_at')
            ->orderBy('score', 'desc')
            ->get();

        $highRisk = $riskScores->where('category', 'tinggi')->count();
        $mediumRisk = $riskScores->where('category', 'sedang')->count();
        $lowRisk = $riskScores->where('category', 'rendah')->count();

        return [
            'scores'       => $riskScores,
            'count_high'   => $highRisk,
            'count_medium' => $mediumRisk,
            'count_low'    => $lowRisk,
            'total'        => $riskScores->count(),
        ];
    }

    /**
     * Mendapatkan data untuk bagian Prescriptive (AHP + TOPSIS ranking).
     *
     * @param int|null $sessionId
     * @return array
     */
    public function getPrescriptiveData(?int $sessionId = null): array
    {
        $session = null;
        $rankings = collect();

        if ($sessionId) {
            $session = AhpSession::with('criteria')->find($sessionId);
        }

        // Jika tidak ada session spesifik, ambil session final terbaru
        if (!$session) {
            $session = AhpSession::with('criteria')
                ->where('is_final', true)
                ->latest()
                ->first();
        }

        if ($session) {
            $rankings = $session->topsisResults()
                ->with('asset')
                ->orderBy('ranking')
                ->get();
        }

        $sessions = AhpSession::orderBy('created_at', 'desc')->get();

        return [
            'session'   => $session,
            'rankings'  => $rankings,
            'sessions'  => $sessions,
        ];
    }

    /**
     * Mendapatkan data untuk bagian Cost.
     *
     * @return array
     */
    public function getCostData(): array
    {
        $totals = $this->costService->totalCosts();

        // Biaya per asset (top 10)
        $topCostAssets = CostAnalysis::select(
            'asset_id',
            DB::raw('SUM(downtime_cost + overtime_cost + labor_cost + sparepart_cost) as total_cost')
        )
            ->join('maintenance_reports', 'cost_analyses.maintenance_report_id', '=', 'maintenance_reports.id')
            ->join('assets', 'maintenance_reports.asset_id', '=', 'assets.id')
            ->whereNotNull('cost_analyses.analyzed_at')
            ->groupBy('asset_id')
            ->orderBy('total_cost', 'desc')
            ->limit(10)
            ->get();

        $topCostAssets->load('asset');

        return [
            'totals'         => $totals,
            'top_cost_assets' => $topCostAssets,
        ];
    }

    /**
     * Mendapatkan data untuk bagian Forecasting.
     *
     * @return array
     */
    public function getForecastData(): array
    {
        $forecastService = app(\App\Services\Predictive\ForecastService::class);

        $historicalData = $forecastService->getHistoricalDowntimeData(6);
        $forecastResults = collect();

        if ($historicalData->count() >= 2) {
            $forecastResults = $forecastService->exponentialSmoothing($historicalData, 0.3, 3);
        }

        return [
            'historical' => $historicalData,
            'forecast'   => $forecastResults,
        ];
    }

    /**
     * Menghasilkan rekomendasi prioritas untuk setiap asset.
     * Menggabungkan ranking TOPSIS, risk score, dan total cost.
     *
     * @param int|null $ahpSessionId
     * @return Collection
     */
    public function generateRecommendations(?int $ahpSessionId = null): Collection
    {
        $session = null;
        if ($ahpSessionId) {
            $session = AhpSession::find($ahpSessionId);
        }
        if (!$session) {
            $session = AhpSession::where('is_final', true)->latest()->first();
        }

        $assets = Asset::with(['riskScores', 'company'])->get();
        $recommendations = collect();

        foreach ($assets as $asset) {
            $riskScore = $asset->riskScores()->latest('calculated_at')->first();
            $topsis = null;
            if ($session) {
                $topsis = TopsisResult::where('ahp_session_id', $session->id)
                    ->where('asset_id', $asset->id)
                    ->first();
            }

            // Total biaya dari laporan asset ini
            $totalCost = (float) CostAnalysis::whereHas('maintenanceReport', function ($q) use ($asset) {
                $q->where('asset_id', $asset->id);
            })->sum(DB::raw('downtime_cost + overtime_cost + labor_cost + sparepart_cost'));

            // Hitung priority score composite
            $priorityScore = $this->compositePriorityScore(
                $topsis?->score ?? 0,
                $riskScore?->score ?? 0,
                $totalCost
            );

            // Tentukan jenis rekomendasi
            $recommendationType = $this->determineRecommendationType(
                $riskScore?->category ?? 'rendah',
                $topsis?->ranking ?? 999,
                $totalCost
            );

            // Deskripsi
            $description = $this->generateDescription(
                $asset,
                $riskScore,
                $topsis,
                $totalCost,
                $recommendationType
            );

            $recommendations->push((object) [
                'asset_id'            => $asset->id,
                'tag_no'              => $asset->tag_no,
                'description'         => $asset->description,
                'company'             => $asset->company?->name ?? '—',
                'risk_score'          => $riskScore?->score ?? 0,
                'risk_category'       => $riskScore?->category ?? 'rendah',
                'topsis_score'        => $topsis?->score ?? 0,
                'topsis_ranking'      => $topsis?->ranking ?? null,
                'total_cost'          => $totalCost,
                'priority_score'      => round($priorityScore, 3),
                'recommendation_type' => $recommendationType,
                'description_text'    => $description,
            ]);
        }

        // Urutkan berdasarkan priority score descending
        $sorted = $recommendations->sortByDesc('priority_score')->values();

        // Simpan ke database
        DecisionRecommendation::truncate();
        foreach ($sorted as $i => $rec) {
            DecisionRecommendation::create([
                'asset_id'            => $rec->asset_id,
                'recommendation_type' => $rec->recommendation_type,
                'priority_score'      => $rec->priority_score,
                'description'         => $rec->description_text,
            ]);
        }

        return $sorted;
    }

    /**
     * Menghitung priority score composite dari tiga dimensi.
     *
     * Bobot: TOPSIS 40%, Risk Score 35%, Cost 25%
     *
     * @param float $topsisScore 0-1
     * @param float $riskScore 0-1
     * @param float $totalCost
     * @return float
     */
    public function compositePriorityScore(float $topsisScore, float $riskScore, float $totalCost): float
    {
        // Normalisasi cost: log10 scale, cap di 100jt
        $costNorm = $totalCost > 0 ? min(log10($totalCost + 1) / 8, 1.0) : 0;

        return 0.40 * $topsisScore + 0.35 * $riskScore + 0.25 * $costNorm;
    }

    /**
     * Menentukan jenis rekomendasi berdasarkan kondisi asset.
     *
     * @param string $riskCategory
     * @param int|null $topsisRanking
     * @param float $totalCost
     * @return string
     */
    public function determineRecommendationType(string $riskCategory, ?int $topsisRanking, float $totalCost): string
    {
        if ($riskCategory === 'tinggi') {
            return 'predictive_maintenance';
        }

        if ($topsisRanking !== null && $topsisRanking <= 3) {
            return 'prioritas_tinggi';
        }

        if ($totalCost > 10000000) {
            return 'cost_optimization';
        }

        if ($riskCategory === 'sedang') {
            return 'monitoring';
        }

        return 'rutin';
    }

    /**
     * Menghasilkan deskripsi rekomendasi dalam Bahasa Indonesia.
     *
     * @param Asset $asset
     * @param RiskScore|null $riskScore
     * @param TopsisResult|null $topsis
     * @param float $totalCost
     * @param string $type
     * @return string
     */
    public function generateDescription(
        Asset $asset,
        ?RiskScore $riskScore,
        ?TopsisResult $topsis,
        float $totalCost,
        string $type
    ): string {
        $parts = [];

        $parts[] = "{$asset->tag_no} — {$asset->description}";

        if ($riskScore) {
            $parts[] = "risiko {$riskScore->category} (skor {$riskScore->score})";
        }

        if ($topsis && $topsis->ranking) {
            $parts[] = "ranking TOPSIS #{$topsis->ranking}";
        }

        if ($totalCost > 0) {
            $parts[] = "biaya Rp " . number_format($totalCost, 0, ',', '.');
        }

        $typeLabels = [
            'predictive_maintenance' => 'Rekomendasi: lakukan predictive maintenance segera.',
            'prioritas_tinggi'       => 'Rekomendasi: prioritas tinggi untuk tindakan perawatan.',
            'cost_optimization'      => 'Rekomendasi: evaluasi biaya untuk optimasi.',
            'monitoring'             => 'Rekomendasi: tingkatkan frekuensi monitoring.',
            'rutin'                  => 'Rekomendasi: lanjutkan perawatan rutin.',
        ];

        $parts[] = $typeLabels[$type] ?? 'Tidak ada rekomendasi khusus.';

        return implode(' — ', $parts);
    }
}
