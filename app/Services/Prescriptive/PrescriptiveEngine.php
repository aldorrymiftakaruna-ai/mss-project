<?php

namespace App\Services\Prescriptive;

use App\Models\AhpSession;
use App\Models\Asset;
use App\Models\DecisionRecommendation;
use App\Models\RiskScore;
use App\Models\TopsisResult;
use App\Services\Predictive\RiskScoreService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrescriptiveEngine
{
    protected AhpService $ahpService;
    protected TopsisService $topsisService;
    protected RiskScoreService $riskScoreService;

    public function __construct(
        AhpService $ahpService,
        TopsisService $topsisService,
        RiskScoreService $riskScoreService
    ) {
        $this->ahpService = $ahpService;
        $this->topsisService = $topsisService;
        $this->riskScoreService = $riskScoreService;
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
     * Menghasilkan rekomendasi prioritas untuk setiap asset.
     * Menggabungkan ranking TOPSIS dan risk score.
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

            // Hitung priority score composite (TOPSIS + Risk Score)
            $priorityScore = $this->compositePriorityScore(
                $topsis?->score ?? 0,
                $riskScore?->score ?? 0
            );

            // Tentukan jenis rekomendasi
            $recommendationType = $this->determineRecommendationType(
                $riskScore?->category ?? 'rendah',
                $topsis?->ranking ?? 999
            );

            // Deskripsi
            $description = $this->generateDescription(
                $asset,
                $riskScore,
                $topsis,
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
                'priority_score'      => round($priorityScore, 3),
                'recommendation_type' => $recommendationType,
                'description_text'    => $description,
            ]);
        }

        // Urutkan berdasarkan priority score descending
        $sorted = $recommendations->sortByDesc('priority_score')->values();

        // Simpan ke database (updateOrCreate agar tidak ada data terhapus)
        $now = now();
        foreach ($sorted as $i => $rec) {
            DecisionRecommendation::updateOrCreate(
                [
                    'asset_id' => $rec->asset_id,
                ],
                [
                    'recommendation_type' => $rec->recommendation_type,
                    'priority_score'      => $rec->priority_score,
                    'description'         => $rec->description_text,
                    'generated_at'        => $now,
                ]
            );
        }

        return $sorted;
    }

    /**
     * Menghitung priority score composite dari dua dimensi.
     *
     * Bobot: TOPSIS 55%, Risk Score 45%
     *
     * @param float $topsisScore 0-1
     * @param float $riskScore 0-1
     * @return float
     */
    public function compositePriorityScore(float $topsisScore, float $riskScore): float
    {
        return 0.55 * $topsisScore + 0.45 * $riskScore;
    }

    /**
     * Menentukan jenis rekomendasi berdasarkan kondisi asset.
     *
     * @param string $riskCategory
     * @param int|null $topsisRanking
     * @return string
     */
    public function determineRecommendationType(string $riskCategory, ?int $topsisRanking): string
    {
        if ($riskCategory === 'tinggi') {
            return 'predictive_maintenance';
        }

        if ($topsisRanking !== null && $topsisRanking <= 3) {
            return 'prioritas_tinggi';
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
     * @param string $type
     * @return string
     */
    public function generateDescription(
        Asset $asset,
        ?RiskScore $riskScore,
        ?TopsisResult $topsis,
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

        $typeLabels = [
            'predictive_maintenance' => 'Rekomendasi: lakukan predictive maintenance segera.',
            'prioritas_tinggi'       => 'Rekomendasi: prioritas tinggi untuk tindakan perawatan.',
            'monitoring'             => 'Rekomendasi: tingkatkan frekuensi monitoring.',
            'rutin'                  => 'Rekomendasi: lanjutkan perawatan rutin.',
        ];

        $parts[] = $typeLabels[$type] ?? 'Tidak ada rekomendasi khusus.';

        return implode(' — ', $parts);
    }
}
