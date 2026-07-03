<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CmFinding;
use App\Models\Employee;
use App\Models\MaintenanceReport;
use App\Models\SparePart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DssController extends Controller
{
    /**
     * Tampilkan halaman DSS — Decision Support System.
     *
     * Menyajikan ringkasan KPI, rekomendasi, analisis maintenance,
     * serta data CM dan produkityas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Ringkasan KPI
        $totalAssets       = Asset::count();
        $equipmentDanger   = Asset::where('status', 'danger')->count();
        $equipmentAlarm    = Asset::where('status', 'alarm')->count();
        $totalLaporan      = MaintenanceReport::count();
        $laporanBulanIni   = MaintenanceReport::whereMonth('created_at', now()->month)->count();
        $stokKritis        = SparePart::whereColumn('stok_tersedia', '<', 'stok_minimum')->count();
        $karyawanAktif     = Employee::where('is_active', true)->count();

        // 1. Tren 30 hari
        $trenBulanan = $this->trenLaporanBulanan();

        // 2. Distribusi jenis
        $distribusiJenis = $this->distribusiJenisPekerjaan();

        // 3. Top 10 equipment (6 bulan)
        $topEquipment = $this->topEquipment();

        // 4. Produktivitas karyawan
        $produktivitasKaryawan = $this->produktivitasKaryawan();

        // 5. Severity CM statistik
        $severityCm = $this->severityCm();

        // 7. Tren severity CM per equipment (filter)
        $daftarEquipment = Asset::whereHas('cmFindings')->orderBy('tag_no')->get(['id', 'tag_no', 'description', 'model']);
        $selectedAssetId = $request->input('asset_id', $daftarEquipment->first()->id ?? null);
        $cmTimeline      = $selectedAssetId ? $this->cmTimeline((int) $selectedAssetId) : collect();
        $selectedAsset   = $selectedAssetId ? Asset::find($selectedAssetId) : null;

        // 9. Statistik downtime (per company)
        $downtimeStats = $this->downtimePerCompany();

        // 10. Statistik lembur bulan ini
        $overtimeStats = $this->overtimeBulanIni();

        // 10b. Data chart downtime per company untuk grafik
        $chartDowntime = $this->chartDowntimeBulanan();

        // 11. Rekomendasi DSS
        $rekomendasi = $this->generateRekomendasi();

        // 12. Chart tahunan
        $chartTahunan = $this->chartTahunan();

        return view('dss.index', compact(
            'totalAssets',
            'equipmentDanger',
            'equipmentAlarm',
            'totalLaporan',
            'laporanBulanIni',
            'stokKritis',
            'karyawanAktif',
            'trenBulanan',
            'distribusiJenis',
            'topEquipment',
            'produktivitasKaryawan',
            'severityCm',

            'daftarEquipment',
            'selectedAssetId',
            'cmTimeline',
            'selectedAsset',
            'downtimeStats',
            'overtimeStats',
            'chartDowntime',
            'rekomendasi',
            'chartTahunan',
        ));
    }

    /**
     * Tren laporan 30 hari terakhir (harian).
     *
     * @return array ['labels' => [...], 'data' => [...]]
     */
    private function trenLaporanBulanan(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $hari = Carbon::today()->subDays($i);
            $labels[] = $hari->isoFormat('DD MMM');
            $data[]   = MaintenanceReport::whereDate('created_at', $hari)->count();
        }

        return compact('labels', 'data');
    }

    /**
     * Distribusi laporan berdasarkan jenis pekerjaan.
     *
     * @return \Illuminate\Support\Collection
     */
    private function distribusiJenisPekerjaan()
    {
        return MaintenanceReport::select('jenis', DB::raw('COUNT(*) as total'))
            ->whereNotNull('jenis')
            ->groupBy('jenis')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Top equipment dengan laporan terbanyak dalam 6 bulan terakhir.
     *
     * @return \Illuminate\Support\Collection
     */
    private function topEquipment()
    {
        $enamBulanLalu = Carbon::now()->subMonths(6);

        return MaintenanceReport::select('asset_id', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $enamBulanLalu)
            ->groupBy('asset_id')
            ->orderByDesc('total')
            ->take(10)
            ->with('asset:id,tag_no,description,status')
            ->get();
    }

    /**
     * Produktivitas karyawan (total menit) — top 10.
     *
     * @return \Illuminate\Support\Collection
     */
    private function produktivitasKaryawan()
    {
        return MaintenanceReport::select('reported_by', DB::raw('SUM(work_duration_minutes) as total_menit, COUNT(*) as total_laporan'))
            ->whereNotNull('work_duration_minutes')
            ->where('work_duration_minutes', '>', 0)
            ->groupBy('reported_by')
            ->orderByDesc('total_menit')
            ->take(10)
            ->with('reporter:id,name')
            ->get();
    }

    /**
     * Statistik severity CM Finding.
     *
     * @return array
     */
    private function severityCm(): array
    {
        $total = CmFinding::count();

        $severities = [
            'critical' => CmFinding::where('severity', 'critical')->count(),
            'high'     => CmFinding::where('severity', 'high')->count(),
            'medium'   => CmFinding::where('severity', 'medium')->count(),
            'low'      => CmFinding::where('severity', 'low')->count(),
        ];

        $openCritical = CmFinding::where('severity', 'critical')
            ->where('status', '!=', 'closed')
            ->count();

        $openHigh = CmFinding::where('severity', 'high')
            ->where('status', '!=', 'closed')
            ->count();

        return compact('total', 'severities', 'openCritical', 'openHigh');
    }

    /**
     * Data laporan per bulan sepanjang tahun ini untuk chart.
     *
     * @return array ['labels' => [...], 'data' => [...]]
     */
    private function chartTahunan(): array
    {
        $labels = [];
        $data   = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $labels[] = Carbon::create()->month($bulan)->isoFormat('MMM');
            $data[]   = MaintenanceReport::whereYear('created_at', now()->year)
                ->whereMonth('created_at', $bulan)
                ->count();
        }

        return compact('labels', 'data');
    }

    /**
     * Ambil timeline CM finding untuk equipment tertentu.
     *
     * @param  int  $assetId
     * @return \Illuminate\Support\Collection
     */
    private function cmTimeline(int $assetId)
    {
        return CmFinding::where('asset_id', $assetId)
            ->orderBy('tanggal', 'asc')
            ->get(['id', 'tanggal', 'kategori', 'severity', 'status', 'analysis', 'finding_code']);
    }

    /**
     * Statistik downtime bulan ini per company (NPA / CES).
     *
     * Menghitung total downtime menit dari laporan yang memiliki downtime_minutes,
     * dikelompokkan berdasarkan company_id dari asset yang direport.
     *
     * @return \Illuminate\Support\Collection
     */
    private function downtimePerCompany()
    {
        $awalBulan  = Carbon::now()->startOfMonth();
        $akhirBulan = Carbon::now()->endOfMonth();

        return MaintenanceReport::select(
                'assets.company_id',
                DB::raw('COALESCE(SUM(maintenance_reports.downtime_minutes), 0) as total_downtime'),
                DB::raw('COUNT(maintenance_reports.id) as total_laporan'),
                DB::raw('SUM(CASE WHEN maintenance_reports.downtime_minutes > 0 THEN 1 ELSE 0 END) as laporan_dengan_downtime')
            )
            ->join('assets', 'maintenance_reports.asset_id', '=', 'assets.id')
            ->whereNotNull('maintenance_reports.downtime_minutes')
            ->where('maintenance_reports.downtime_minutes', '>', 0)
            ->whereBetween('maintenance_reports.created_at', [$awalBulan, $akhirBulan])
            ->groupBy('assets.company_id')
            ->get()
            ->keyBy('company_id');
    }

    /**
     * Statistik lembur bulan ini.
     *
     * Menghitung total jam lembur, jumlah laporan lembur, dan breakdown per teknisi.
     *
     * @return array
     */
    private function overtimeBulanIni(): array
    {
        $awalBulan  = Carbon::now()->startOfMonth();
        $akhirBulan = Carbon::now()->endOfMonth();

        $laporanLembur = MaintenanceReport::where('is_overtime', true)
            ->whereBetween('created_at', [$awalBulan, $akhirBulan]);

        $totalLaporanLembur = $laporanLembur->count();
        $totalJamLembur     = (float) $laporanLembur->sum('overtime_hours');

        // Top 5 teknisi dengan lembur terbanyak
        $topTeknisi = MaintenanceReport::select(
                'reported_by',
                DB::raw('COUNT(*) as total_laporan'),
                DB::raw('COALESCE(SUM(overtime_hours), 0) as total_jam')
            )
            ->where('is_overtime', true)
            ->whereBetween('created_at', [$awalBulan, $akhirBulan])
            ->groupBy('reported_by')
            ->orderByDesc('total_jam')
            ->take(5)
            ->with('reporter:id,name')
            ->get();

        return [
            'total_laporan' => $totalLaporanLembur,
            'total_jam'     => $totalJamLembur,
            'top_teknisi'   => $topTeknisi,
        ];
    }

    /**
     * Data chart downtime per company per bulan (12 bulan terakhir).
     *
     * @return array ['labels' => [...], 'npa' => [...], 'ces' => [...]]
     */
    private function chartDowntimeBulanan(): array
    {
        $labels = [];
        $npa    = [];
        $ces    = [];

        for ($i = 11; $i >= 0; $i--) {
            $tgl = Carbon::now()->subMonthsNoOverflow($i);
            $bulan = $tgl->month;
            $tahun = $tgl->year;

            $labels[] = $tgl->isoFormat('MMM YY');

            $query = MaintenanceReport::select(
                    DB::raw('COALESCE(SUM(maintenance_reports.downtime_minutes), 0) as total')
                )
                ->join('assets', 'maintenance_reports.asset_id', '=', 'assets.id')
                ->whereNotNull('maintenance_reports.downtime_minutes')
                ->whereYear('maintenance_reports.created_at', $tahun)
                ->whereMonth('maintenance_reports.created_at', $bulan)
                ->where('assets.company_id', 1); // NPA

            $npa[] = (int) $query->value('total') ?? 0;

            $queryCes = MaintenanceReport::select(
                    DB::raw('COALESCE(SUM(maintenance_reports.downtime_minutes), 0) as total')
                )
                ->join('assets', 'maintenance_reports.asset_id', '=', 'assets.id')
                ->whereNotNull('maintenance_reports.downtime_minutes')
                ->whereYear('maintenance_reports.created_at', $tahun)
                ->whereMonth('maintenance_reports.created_at', $bulan)
                ->where('assets.company_id', 2); // CES

            $ces[] = (int) $queryCes->value('total') ?? 0;
        }

        return compact('labels', 'npa', 'ces');
    }

    /**
     * Generate rekomendasi preskriptif berbasis data.
     *
     * @return array
     */
    private function generateRekomendasi(): array
    {
        $rekomendasi = [];

        // 1. Equipment danger
        $equipmentDanger = Asset::where('status', 'danger')->count();
        if ($equipmentDanger > 0) {
            $rekomendasi[] = [
                'level' => 'high',
                'title' => "{$equipmentDanger} equipment berstatus danger",
                'desc'  => 'Butuh tindakan corrective segera. Cek data CM untuk akar masalah.',
            ];
        }

        // 2. Equipment alarm
        $equipmentAlarm = Asset::where('status', 'alarm')->count();
        if ($equipmentAlarm > 0) {
            $rekomendasi[] = [
                'level' => 'med',
                'title' => "{$equipmentAlarm} equipment dalam status alarm",
                'desc'  => 'Vibrasi/temperatur melebihi batas alarm. Jadwalkan pengecekan CM lanjutan.',
            ];
        }

        // 3. CM severity tinggi masih open
        $totalCmHigh = CmFinding::whereIn('severity', ['high', 'critical'])
            ->where('status', '!=', 'closed')
            ->count();
        if ($totalCmHigh > 0) {
            $rekomendasi[] = [
                'level' => $totalCmHigh > 3 ? 'high' : 'med',
                'title' => "{$totalCmHigh} temuan CM (high/critical) masih open",
                'desc'  => 'Prioritaskan action plan untuk temuan severity tinggi yang belum ditindaklanjuti.',
            ];
        }

        // 4. Stok sparepart kritis
        $stokKritis = SparePart::whereColumn('stok_tersedia', '<', 'stok_minimum')->count();
        if ($stokKritis > 0) {
            $rekomendasi[] = [
                'level' => 'med',
                'title' => "{$stokKritis} sparepart stok di bawah minimum",
                'desc'  => 'Segera lakukan reorder. Cek detail di menu Sparepart.',
            ];
        }

        // 5. Equipment dengan MTBF rendah (danger)
        $equipments = Asset::with('maintenanceReports')->get();
        $equipDangerMtbf = [];
        foreach ($equipments as $eq) {
            $reports = $eq->maintenanceReports->sortBy('created_at')->pluck('created_at');
            if ($reports->count() < 2) continue;
            $totalSelisih = 0;
            $jumlahInterval = 0;
            $prev = null;
            foreach ($reports as $tgl) {
                if ($prev !== null) { $totalSelisih += $prev->diffInDays($tgl); $jumlahInterval++; }
                $prev = $tgl;
            }
            $mtbfHari = $jumlahInterval > 0 ? round($totalSelisih / $jumlahInterval, 1) : 0;
            if ($mtbfHari > 0 && $mtbfHari < 30) {
                $equipDangerMtbf[] = ['tag' => $eq->tag_no, 'mtbf' => $mtbfHari, 'desc' => $eq->description];
            }
        }
        if (count($equipDangerMtbf) > 0) {
            $namaRusak = collect($equipDangerMtbf)->take(3)->pluck('tag')->implode(', ');
            $banyak = count($equipDangerMtbf);
            $rekomendasi[] = [
                'level' => 'high',
                'title' => "{$banyak} equipment dengan MTBF kritis",
                'desc'  => "Interval kegagalan sangat pendek: {$namaRusak}. Evaluasi desain/reliability equipment ini.",
            ];
        }

        // 6. Tidak ada laporan dalam 7 hari
        $laporan7Hari = MaintenanceReport::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        if ($laporan7Hari === 0) {
            $rekomendasi[] = [
                'level' => 'med',
                'title' => 'Tidak ada laporan maintenance dalam 7 hari terakhir',
                'desc'  => 'Pastikan teknisi melakukan reporting secara rutin. Cek koneksi bot Telegram.',
            ];
        }

        // 7. Rekomendasi downtime tinggi
        $downtimeStats = $this->downtimePerCompany();
        foreach ($downtimeStats as $companyId => $stat) {
            if ($stat->total_downtime > 0) {
                $nama = $companyId == 1 ? 'NPA' : 'CES';
                $jam = round($stat->total_downtime / 60, 1);
                if ($stat->total_downtime >= 600) { // >10 jam
                    $rekomendasi[] = [
                        'level' => 'high',
                        'title' => "Downtime {$nama} {$jam} jam bulan ini",
                        'desc'  => "Total {$stat->laporan_dengan_downtime} laporan dengan downtime. Investigasi equipment paling berkontribusi.",
                    ];
                } elseif ($stat->total_downtime >= 120) {
                    $rekomendasi[] = [
                        'level' => 'med',
                        'title' => "Downtime {$nama} {$jam} jam bulan ini",
                        'desc'  => "Downtime mulai signifikan. Pantau tren dan lakukan analisis akar masalah.",
                    ];
                }
            }
        }

        // 8. Rekomendasi lembur
        $overtimeStats = $this->overtimeBulanIni();
        if ($overtimeStats['total_jam'] > 0) {
            if ($overtimeStats['total_jam'] >= 40) {
                $rekomendasi[] = [
                    'level' => 'med',
                    'title' => "Total {$overtimeStats['total_jam']} jam lembur bulan ini",
                    'desc'  => "{$overtimeStats['total_laporan']} laporan dengan lembur. Evaluasi distribusi beban kerja teknisi.",
                ];
            }
        }

        return $rekomendasi;
    }
}