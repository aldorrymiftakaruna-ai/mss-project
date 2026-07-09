<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\WeibullResult;
use App\Services\Predictive\WeibullService;
use Illuminate\Http\Request;

class WeibullController extends Controller
{
    protected WeibullService $weibullService;

    public function __construct(WeibullService $weibullService)
    {
        $this->weibullService = $weibullService;
    }

    /**
     * Halaman index — daftar asset dengan parameter Weibull.
     */
    public function index()
    {
        $results = WeibullResult::with('asset.company')
            ->whereNotNull('calculated_at')
            ->orderBy('calculated_at', 'desc')
            ->get();

        return view('predictive.weibull', compact('results'));
    }

    /**
     * Detail Weibull untuk satu asset.
     */
    public function detail(Asset $asset)
    {
        $result = WeibullResult::where('asset_id', $asset->id)
            ->latest('calculated_at')
            ->first();

        if (!$result) {
            $result = $this->weibullService->estimateForAsset($asset);
            return view('predictive.weibull-detail', compact('asset', 'result'));
        }

        return view('predictive.weibull-detail', compact('asset', 'result'));
    }

    /**
     * Hitung Weibull untuk semua asset.
     */
    public function calculateAll()
    {
        $result = $this->weibullService->estimateAll();
        $total = $result['processed'] + $result['skipped'];

        $message = "Weibull: {$result['processed']} berhasil, {$result['skipped']} dilewati (dari {$total} asset).";

        if ($result['processed'] === 0) {
            $message .= ' Butuh minimal 3 laporan maintenance per asset.';
            return redirect()
                ->route('weibull.index')
                ->with('info', $message);
        }

        return redirect()
            ->route('weibull.index')
            ->with('success', $message);
    }

    /**
     * Hitung Weibull untuk satu asset.
     */
    public function calculateAsset(Asset $asset)
    {
        $result = $this->weibullService->estimateForAsset($asset);

        if ($result['beta'] !== null) {
            return redirect()
                ->route('weibull.detail', $asset->id)
                ->with('success', "Weibull berhasil dihitung. β={$result['beta']}, η={$result['eta']}");
        }

        return redirect()
            ->route('weibull.detail', $asset->id)
            ->with('error', $result['message'] ?? 'Gagal menghitung Weibull.');
    }
}
