<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CmFinding;
use App\Models\Employee;
use App\Models\MaintenanceReport;
use App\Models\ManpowerLog;
use App\Models\SparePart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard utama.
     *
     * Menyajikan ringkasan deskriptif (KPI, chart 7-hari),
     * prediktif (Top 5 equipment sering rusak, CM alert),
     * dan insight KPI (equipment danger, sparepart kritis, downtime vs KPI, lembur vs KPI).
     */
    public function index()
    {
        // ──────────────────────────────────────────────
        // 1. KPI Cards
        // ──────────────────────────────────────────────
        $totalAssets       = Asset::count();
        $equipmentDanger   = Asset::where('status', 'danger')->count();
        $laporanHariIni    = MaintenanceReport::whereDate('created_at', today())->count();
        $stokKritis        = SparePart::whereColumn('stok_tersedia', '<', 'stok_minimum')->count();

        // Durasi Kerja Karyawan minggu ini (dari work_duration_minutes)
        $durasiKerja = $this->hitungDurasiKerjaMingguan();
        $totalMenitMingguIni  = $durasiKerja['total_menit'];
        $totalJamMingguIni    = $durasiKerja['total_jam'];
        $totalKaryawanAktif   = $durasiKerja['total_karyawan'];
        $totalLaporanMinggu   = $durasiKerja['total_laporan'];

        // Downtime & Lembur ringkasan
        $totalDowntimeBulanIni = (int) MaintenanceReport::whereMonth('created_at', now()->month)
            ->whereNotNull('downtime_minutes')
            ->sum('downtime_minutes');

        $totalLemburBulanIni = (float) MaintenanceReport::whereMonth('created_at', now()->month)
            ->where('is_overtime', true)
            ->sum('overtime_hours');

        $totalLaporanLemburBulanIni = MaintenanceReport::whereMonth('created_at', now()->month)
            ->where('is_overtime', true)
            ->count();

        // ──────────────────────────────────────────────
        // 2. Mini Chart — Laporan 7 hari terakhir
        // ──────────────────────────────────────────────
        $chart7Hari = $this->chartLaporan7Hari();

        // ──────────────────────────────────────────────
        // 3. Top 5 Equipment paling sering rusak bulan ini
        // ──────────────────────────────────────────────
        $topEquipmentRusak = $this->topEquipmentRusakBulanIni();

        // ──────────────────────────────────────────────
        // 4. CM Alert terbaru
        // ──────────────────────────────────────────────
        $cmAlerts = $this->cmAlertsTerbaru();

        // ──────────────────────────────────────────────
        // 5. Insight KPI — menggantikan rekomendasi DSS
        // ──────────────────────────────────────────────
        $kpiDowntime   = $this->kpiInsightDowntime($totalDowntimeBulanIni);
        $kpiLembur     = $this->kpiInsightLembur($totalLemburBulanIni);
        $equipmentDangerList = Asset::where('status', 'danger')->take(5)->get(['id', 'tag_no', 'description', 'status']);
        $sparepartKritisList = SparePart::whereColumn('stok_tersedia', '<', 'stok_minimum')->take(5)->get();

        return view('dashboard', compact(
            'totalAssets',
            'equipmentDanger',
            'laporanHariIni',
            'stokKritis',
            'totalMenitMingguIni',
            'totalJamMingguIni',
            'totalKaryawanAktif',
            'totalLaporanMinggu',
            'totalDowntimeBulanIni',
            'totalLemburBulanIni',
            'totalLaporanLemburBulanIni',
            'chart7Hari',
            'topEquipmentRusak',
            'cmAlerts',
            'equipmentDangerList',
            'sparepartKritisList',
            'kpiDowntime',
            'kpiLembur',
        ));
    }

    /**
     * Hitung total durasi kerja karyawan minggu ini dari work_duration_minutes.
     *
     * @return array ['total_menit' => int, 'total_jam' => float, 'total_karyawan' => int, 'total_laporan' => int]
     */
    private function hitungDurasiKerjaMingguan(): array
    {
        $mulaiMinggu = Carbon::now()->startOfWeek();
        $akhirMinggu = Carbon::now()->endOfWeek();

        $totalKaryawanAktif = Employee::where('is_active', true)->count();

        $reportMingguIni = MaintenanceReport::whereBetween('created_at', [$mulaiMinggu, $akhirMinggu]);

        $totalMenit   = (int) $reportMingguIni->sum('work_duration_minutes');
        $totalLaporan = $reportMingguIni->count();

        return [
            'total_menit'     => $totalMenit,
            'total_jam'       => $totalMenit > 0 ? round($totalMenit / 60, 1) : 0,
            'total_karyawan'  => $totalKaryawanAktif,
            'total_laporan'   => $totalLaporan,
        ];
    }

    /**
     * Data laporan 7 hari terakhir untuk chart.
     *
     * @return array
     */
    private function chartLaporan7Hari(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $hari = Carbon::today()->subDays($i);
            $labels[] = $hari->isoFormat('dddd');
            $data[]   = MaintenanceReport::whereDate('created_at', $hari)->count();
        }

        return compact('labels', 'data');
    }

    /**
     * Top 5 equipment dengan laporan maintenance terbanyak bulan ini.
     *
     * @return \Illuminate\Support\Collection
     */
    private function topEquipmentRusakBulanIni()
    {
        $awalBulan  = Carbon::now()->startOfMonth();
        $akhirBulan = Carbon::now()->endOfMonth();

        return MaintenanceReport::select('asset_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$awalBulan, $akhirBulan])
            ->groupBy('asset_id')
            ->orderByDesc('total')
            ->take(5)
            ->with('asset:id,tag_no,description,status')
            ->get();
    }

    /**
     * CM Alert — temuan CM dengan severity tinggi yang masih open.
     *
     * @return \Illuminate\Support\Collection
     */
    private function cmAlertsTerbaru()
    {
        return CmFinding::whereIn('severity', ['high', 'critical'])
            ->where('status', '!=', 'closed')
            ->with('asset:id,tag_no,description')
            ->latest('tanggal')
            ->take(5)
            ->get();
    }

    /**
     * Insight KPI downtime terhadap batas KPI (default 20 jam/bulan = 1200 menit).
     *
     * @param int $totalDowntimeMenit
     * @return array ['total_jam' => float, 'kpi_jam' => int, 'sisa_jam' => float, 'persen' => float, 'status' => string, 'pesan' => string]
     */
    private function kpiInsightDowntime(int $totalDowntimeMenit): array
    {
        $kpiMenit = 1200; // 20 jam
        $totalJam = round($totalDowntimeMenit / 60, 1);
        $kpiJam   = $kpiMenit / 60;
        $sisaJam  = round(max($kpiJam - $totalJam, 0), 1);
        $persen   = $kpiJam > 0 ? round(($totalJam / $kpiJam) * 100, 1) : 0;

        if ($totalJam >= $kpiJam) {
            $status = 'danger';
            $pesan  = "Downtime sudah melewati batas KPI ({$kpiJam} jam)! Saat ini {$totalJam} jam.";
        } elseif ($persen >= 80) {
            $status = 'warning';
            $pesan  = "Downtime {$totalJam} jam — sisa {$sisaJam} jam lagi mencapai KPI ({$kpiJam} jam). Waspada.";
        } else {
            $status = 'safe';
            $pesan  = "Downtime {$totalJam} jam dari KPI {$kpiJam} jam. Sisa kuota {$sisaJam} jam. Masih aman.";
        }

        return [
            'total_jam'  => $totalJam,
            'kpi_jam'    => $kpiJam,
            'sisa_jam'   => $sisaJam,
            'persen'     => $persen,
            'status'     => $status,
            'pesan'      => $pesan,
        ];
    }

    /**
     * Insight KPI lembur terhadap batas KPI (default 40 jam/bulan).
     *
     * @param float $totalLemburJam
     * @return array ['total_jam' => float, 'kpi_jam' => int, 'sisa_jam' => float, 'persen' => float, 'status' => string, 'pesan' => string]
     */
    private function kpiInsightLembur(float $totalLemburJam): array
    {
        $kpiJam  = 40;
        $sisaJam = round(max($kpiJam - $totalLemburJam, 0), 1);
        $persen  = $kpiJam > 0 ? round(($totalLemburJam / $kpiJam) * 100, 1) : 0;

        if ($totalLemburJam >= $kpiJam) {
            $status = 'danger';
            $pesan  = "Lembur sudah melebihi batas KPI ({$kpiJam} jam)! Saat ini {$totalLemburJam} jam.";
        } elseif ($persen >= 80) {
            $status = 'warning';
            $pesan  = "Lembur {$totalLemburJam} jam — sisa {$sisaJam} jam lagi mencapai KPI. Evaluasi beban kerja.";
        } else {
            $status = 'safe';
            $pesan  = "Lembur {$totalLemburJam} jam dari KPI {$kpiJam} jam. Sisa kuota {$sisaJam} jam. Normal.";
        }

        return [
            'total_jam' => $totalLemburJam,
            'kpi_jam'   => $kpiJam,
            'sisa_jam'  => $sisaJam,
            'persen'    => $persen,
            'status'    => $status,
            'pesan'     => $pesan,
        ];
    }
}