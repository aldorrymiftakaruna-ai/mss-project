<?php

namespace App\Services\Cost;

use App\Models\CostAnalysis;
use App\Models\CostRate;
use App\Models\MaintenanceReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceCostService
{
    /**
     * Menghitung biaya downtime dari laporan maintenance.
     *
     * @param MaintenanceReport $report
     * @param CostRate $rate
     * @return float
     */
    public function calculateDowntimeCost(MaintenanceReport $report, CostRate $rate): float
    {
        $minutes = $report->downtime_minutes ?? 0;

        return round($minutes * (float) $rate->downtime_rate_per_min, 2);
    }

    /**
     * Menghitung biaya overtime dari laporan maintenance.
     *
     * @param MaintenanceReport $report
     * @param CostRate $rate
     * @return float
     */
    public function calculateOvertimeCost(MaintenanceReport $report, CostRate $rate): float
    {
        $hours = $report->overtime_hours ?? 0;

        return round($hours * (float) $rate->overtime_rate_per_hour, 2);
    }

    /**
     * Menghitung biaya tenaga kerja berdasarkan manpower log.
     *
     * @param MaintenanceReport $report
     * @param CostRate $rate
     * @return float
     */
    public function calculateLaborCost(MaintenanceReport $report, CostRate $rate): float
    {
        $manpowerLogs = $report->manpowerLogs;

        if ($manpowerLogs->isEmpty()) {
            return 0;
        }

        $totalMinutes = $manpowerLogs->sum(function ($log) {
            if ($log->start_time && $log->end_time) {
                return $log->start_time->diffInMinutes($log->end_time);
            }
            return $report->work_duration_minutes ?? 0;
        });

        return round($totalMinutes * (float) $rate->downtime_rate_per_min, 2);
    }

    /**
     * Menghitung biaya sparepart dari laporan maintenance.
     * Asumsi: sparepart_cost diambil dari tabel pivot atau kolom.
     *
     * @param MaintenanceReport $report
     * @return float
     */
    public function calculateSparepartCost(MaintenanceReport $report): float
    {
        // Jika ada pivot sparepart_usage, jumlahkan.
        // Fallback: kolom sparepart_cost jika ada di report.
        $cost = 0;

        if (method_exists($report, 'sparepartUsages')) {
            $cost = $report->sparepartUsages->sum(function ($usage) {
                return (float) ($usage->quantity * $usage->unit_price ?? 0);
            });
        }

        return round($cost, 2);
    }

    /**
     * Menganalisis satu laporan maintenance dan menyimpan hasilnya.
     *
     * @param int $reportId
     * @return CostAnalysis
     */
    public function analyzeReport(int $reportId): CostAnalysis
    {
        $report = MaintenanceReport::with('manpowerLogs')->findOrFail($reportId);

        /**
         * Cari rate yang berlaku berdasarkan company_id asset dan effective_date.
         * Gunakan rate pertama jika tidak ditemukan.
         */
        $companyId = $report->asset?->company_id ?? 1;
        $rate = CostRate::where('company_id', $companyId)
            ->where('effective_date', '<=', $report->tanggal ?? now())
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$rate) {
            $rate = CostRate::create([
                'company_id' => $companyId,
                'downtime_rate_per_min' => 0,
                'overtime_rate_per_hour' => 0,
                'effective_date' => $report->tanggal ?? now(),
            ]);
        }

        $downtimeCost = $this->calculateDowntimeCost($report, $rate);
        $overtimeCost = $this->calculateOvertimeCost($report, $rate);
        $laborCost = $this->calculateLaborCost($report, $rate);
        $sparepartCost = $this->calculateSparepartCost($report);

        return CostAnalysis::updateOrCreate(
            ['maintenance_report_id' => $reportId],
            [
                'downtime_cost' => $downtimeCost,
                'overtime_cost' => $overtimeCost,
                'labor_cost' => $laborCost,
                'sparepart_cost' => $sparepartCost,
                'analyzed_at' => now(),
            ]
        );
    }

    /**
     * Mendapatkan ringkasan biaya per bulan.
     *
     * @param int|null $companyId
     * @param int $months Jumlah bulan ke belakang
     * @return Collection
     */
    public function monthlySummary(?int $companyId = null, int $months = 12): Collection
    {
        $query = CostAnalysis::select(
            DB::raw("DATE_FORMAT(analyzed_at, '%Y-%m') as bulan"),
            DB::raw('SUM(downtime_cost) as total_downtime'),
            DB::raw('SUM(overtime_cost) as total_overtime'),
            DB::raw('SUM(labor_cost) as total_labor'),
            DB::raw('SUM(sparepart_cost) as total_sparepart'),
            DB::raw('SUM(downtime_cost + overtime_cost + labor_cost + sparepart_cost) as grand_total'),
        )
            ->whereNotNull('analyzed_at')
            ->where('analyzed_at', '>=', now()->subMonths($months))
            ->groupBy('bulan')
            ->orderBy('bulan');

        if ($companyId) {
            $query->whereHas('maintenanceReport.asset', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        return $query->get();
    }

    /**
     * Mendapatkan total biaya kumulatif.
     *
     * @param int|null $companyId
     * @return array
     */
    public function totalCosts(?int $companyId = null): array
    {
        $query = CostAnalysis::query();

        if ($companyId) {
            $query->whereHas('maintenanceReport.asset', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        return [
            'total_downtime' => (float) $query->sum('downtime_cost'),
            'total_overtime' => (float) $query->sum('overtime_cost'),
            'total_labor' => (float) $query->sum('labor_cost'),
            'total_sparepart' => (float) $query->sum('sparepart_cost'),
            'grand_total' => (float) $query->sum(DB::raw('downtime_cost + overtime_cost + labor_cost + sparepart_cost')),
        ];
    }
}
