<?php

namespace App\Http\Controllers;

use App\Models\CostRate;
use App\Models\Company;
use App\Models\MaintenanceReport;
use App\Services\Cost\MaintenanceCostService;
use Illuminate\Http\Request;

class CostController extends Controller
{
    protected MaintenanceCostService $costService;

    public function __construct(MaintenanceCostService $costService)
    {
        $this->costService = $costService;
    }

    /**
     * Menampilkan dashboard analisis biaya.
     */
    public function index()
    {
        $companies = Company::orderBy('name')->get();
        $selectedCompanyId = request('company_id');

        $monthlySummary = $this->costService->monthlySummary(
            $selectedCompanyId ? (int) $selectedCompanyId : null
        );

        $totalCosts = $this->costService->totalCosts(
            $selectedCompanyId ? (int) $selectedCompanyId : null
        );

        $recentAnalyses = \App\Models\CostAnalysis::with('maintenanceReport.asset')
            ->whereNotNull('analyzed_at')
            ->latest('analyzed_at')
            ->limit(10)
            ->get();

        return view('cost.index', compact(
            'companies',
            'selectedCompanyId',
            'monthlySummary',
            'totalCosts',
            'recentAnalyses'
        ));
    }

    /**
     * Menampilkan halaman pengaturan rate biaya.
     */
    public function settings()
    {
        $companies = Company::orderBy('name')->get();
        $rates = CostRate::with('company')
            ->orderBy('effective_date', 'desc')
            ->get();

        return view('cost.settings', compact('companies', 'rates'));
    }

    /**
     * Menyimpan atau memperbarui rate biaya.
     */
    public function updateRates(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'downtime_rate_per_min' => 'required|numeric|min:0',
            'overtime_rate_per_hour' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        CostRate::create($validated);

        return redirect()
            ->route('cost.settings')
            ->with('success', 'Rate biaya berhasil disimpan.');
    }

    /**
     * Menganalisis ulang semua laporan yang belum dianalisis.
     */
    public function reanalyzeAll()
    {
        $reports = MaintenanceReport::whereDoesntHave('costAnalysis')->get();

        $count = 0;
        foreach ($reports as $report) {
            $this->costService->analyzeReport($report->id);
            $count++;
        }

        return redirect()
            ->route('cost.index')
            ->with('success', "Berhasil menganalisis {$count} laporan.");
    }

    /**
     * Menganalisis ulang laporan tertentu.
     */
    public function reanalyze(int $reportId)
    {
        $this->costService->analyzeReport($reportId);

        return redirect()
            ->route('cost.index')
            ->with('success', 'Laporan berhasil dianalisis ulang.');
    }
}
