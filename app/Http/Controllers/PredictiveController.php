<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\RiskScore;
use App\Services\Predictive\RiskScoreService;
use Illuminate\Http\Request;

class PredictiveController extends Controller
{
    protected RiskScoreService $riskScoreService;

    public function __construct(RiskScoreService $riskScoreService)
    {
        $this->riskScoreService = $riskScoreService;
    }

    /**
     * Dashboard risiko — ringkasan semua equipment.
     */
    public function index()
    {
        $riskScores = RiskScore::with('asset.company')
            ->orderBy('score', 'desc')
            ->get();

        $total = $riskScores->count();
        $tinggi = $riskScores->where('category', 'tinggi')->count();
        $sedang = $riskScores->where('category', 'sedang')->count();
        $rendah = $riskScores->where('category', 'rendah')->count();

        // Equipment tanpa data CM (tidak punya risk score)
        $noCmCount = Asset::whereDoesntHave('riskScores')->count();

        // Rata-rata skor
        $avgScore = $total > 0 ? round($riskScores->avg('score'), 4) : 0;

        return view('predictive.index', compact(
            'riskScores',
            'total',
            'tinggi',
            'sedang',
            'rendah',
            'noCmCount',
            'avgScore',
        ));
    }

    /**
     * Detail risiko untuk satu asset + grafik tren.
     */
    public function detail(Asset $asset)
    {
        $riskScore = RiskScore::where('asset_id', $asset->id)->first();

        $trend = $this->riskScoreService->getTrendChartData($asset, 20);

        $thresholds = $asset->thresholds();

        $recentFindings = $asset->cmFindings()
            ->orderBy('tanggal', 'desc')
            ->take(10)
            ->get();

        return view('predictive.detail', compact(
            'asset',
            'riskScore',
            'trend',
            'thresholds',
            'recentFindings',
        ));
    }

    /**
     * Hitung ulang semua risk score (AJAX).
     */
    public function recalculate()
    {
        $result = $this->riskScoreService->calculateAll();

        return response()->json([
            'success'   => true,
            'message'   => "Risk score dihitung: {$result['processed']} sukses, {$result['errors']} gagal.",
            'processed' => $result['processed'],
            'errors'    => $result['errors'],
        ]);
    }

    /**
     * Hitung ulang untuk satu asset (AJAX).
     */
    public function recalculateAsset(Asset $asset)
    {
        $result = $this->riskScoreService->calculateForAsset($asset);

        RiskScore::updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'score'           => $result['score'],
                'category'        => $result['category'],
                'parameters_json' => $result['parameters'],
                'calculated_at'   => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'score'   => $result['score'],
            'category'=> $result['category'],
        ]);
    }
}
