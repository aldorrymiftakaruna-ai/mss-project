<?php

namespace App\Http\Controllers;

use App\Models\DecisionRecommendation;
use App\Services\Prescriptive\PrescriptiveEngine;
use Illuminate\Http\Request;

class IntegratedDssController extends Controller
{
    protected PrescriptiveEngine $engine;

    public function __construct(PrescriptiveEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Menampilkan dashboard DSS terintegrasi (waterfall).
     */
    public function index()
    {
        // Predictive Section
        $predictiveData = $this->engine->getPredictiveData();

        // Prescriptive Section
        $sessionId = request('ahp_session_id') ? (int) request('ahp_session_id') : null;
        $prescriptiveData = $this->engine->getPrescriptiveData($sessionId);

        // Rekomendasi Final — ambil yang terbaru (generated_at), urut prioritas
        $recommendations = DecisionRecommendation::with('asset')
            ->orderBy('generated_at', 'desc')
            ->orderBy('priority_score', 'desc')
            ->get();

        if ($recommendations->isEmpty()) {
            $recommendations = $this->engine->generateRecommendations($sessionId);
        }

        $topRecommendation = $recommendations->first();

        return view('integrated.index', compact(
            'predictiveData',
            'prescriptiveData',
            'recommendations',
            'topRecommendation'
        ));
    }

    /**
     * Memicu ulang kalkulasi semua rekomendasi.
     */
    public function recalculate(Request $request)
    {
        $sessionId = $request->input('ahp_session_id') ? (int) $request->input('ahp_session_id') : null;

        $this->engine->generateRecommendations($sessionId);

        return redirect()
            ->route('dss.integrated')
            ->with('success', 'Rekomendasi berhasil diperbarui.');
    }
}
