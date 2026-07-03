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
     * dan preskriptif (rekomendasi DSS).
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
        // 5. Rekomendasi DSS
        // ──────────────────────────────────────────────
        $rekomendasi = $this->generateRekomendasi(
              $equipmentDanger,
              $stokKritis,
              $durasiKerja
          );

        return view('dashboard', compact(
            'totalAssets',
            'equipmentDanger',
            'laporanHariIni',
            'stokKritis',
            'totalMenitMingguIni',
            'totalJamMingguIni',
            'totalKaryawanAktif',
            'totalLaporanMinggu',
            'chart7Hari',
            'topEquipmentRusak',
            'cmAlerts',
            'rekomendasi',
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
     * Generate rekomendasi DSS berdasarkan data terkini.
     *
     * @param int   $equipmentDanger
     * @param int   $stokKritis
     * @param array $efektif
     * @return array
     */
    private function generateRekomendasi(int $equipmentDanger, int $stokKritis, array $durasiKerja): array
    {
        $rekomendasi = [];

        // 1. Equipment danger
        if ($equipmentDanger > 0) {
            $rekomendasi[] = [
                'level' => 'high',
                'title' => "{$equipmentDanger} equipment berstatus danger",
                'desc'  => 'Butuh tindakan corrective segera. Cek hasil CM terbaru untuk memastikan akar masalah.',
            ];
        }

        // 2. Kenaikan tren vibrasi/temperature
        $totalCmHigh = CmFinding::whereIn('severity', ['high', 'critical'])
            ->where('status', '!=', 'closed')
            ->count();

        if ($totalCmHigh > 0) {
            $rekomendasi[] = [
                'level' => $totalCmHigh > 3 ? 'high' : 'med',
                'title' => "{$totalCmHigh} temuan CM severity tinggi",
                'desc'  => 'Indikasi kenaikan vibrasi/temperatur. Percepat jadwal corrective maintenance.',
            ];
        }

        // 3. Stok sparepart kritis
        if ($stokKritis > 0) {
            $rekomendasi[] = [
                'level' => 'med',
                'title' => "{$stokKritis} sparepart stok kritis",
                'desc'  => 'Stok di bawah minimum. Segera reorder. Tinjau apakah perlu adjustment minimum stok.',
            ];
        }

        // 4. Total jam kerja minggu ini
        if ($durasiKerja['total_laporan'] > 0 && $durasiKerja['total_jam'] < 10 && $durasiKerja['total_karyawan'] > 0) {
            $rekomendasi[] = [
                'level' => 'med',
                'title' => "Total {$durasiKerja['total_jam']} jam kerja minggu ini — rendah",
                'desc'  => "{$durasiKerja['total_laporan']} laporan dari {$durasiKerja['total_karyawan']} karyawan. Pastikan reporting berjalan optimal.",
            ];
        } elseif ($durasiKerja['total_jam'] > 80 && $durasiKerja['total_karyawan'] > 0) {
            $rekomendasi[] = [
                'level' => 'low',
                'title' => "Total {$durasiKerja['total_jam']} jam kerja minggu ini — padat",
                'desc'  => 'Aktivitas maintenance tinggi. Pastikan tidak ada overloading pada teknisi.',
            ];
        }

        // 5. Equipment sering rusak → RCA
        $topRusak = $this->topEquipmentRusakBulanIni();
        if ($topRusak->isNotEmpty()) {
            $most = $topRusak->first();
            if ($most->total >= 3) {
                $rekomendasi[] = [
                    'level' => 'med',
                    'title' => "{$most->asset->tag_no} — {$most->total}x rusak bulan ini",
                    'desc'  => 'Frekuensi tinggi. Perlu Root Cause Analysis (RCA) untuk mencegah recurring failure.',
                ];
            }
        }

        // 6. Rekomendasi minimum stok sparepart
        $this->rekomendasiMinimumStok($rekomendasi, $stokKritis);

        // 7. UCL/LCL durasi pekerjaan
        $this->rekomendasiUclLcl($rekomendasi);

        return $rekomendasi;
    }

    /**
     * Rekomendasi penyesuaian minimum stok berdasarkan riwayat pemakaian.
     *
     * @param array &$rekomendasi
     * @param int   $stokKritis
     */
    private function rekomendasiMinimumStok(array &$rekomendasi, int $stokKritis): void
    {
        if ($stokKritis < 1) {
            return;
        }

        $seringKritis = SparePart::whereColumn('stok_tersedia', '<', 'stok_minimum')
            ->where('stok_minimum', '>', 0)
            ->get();

        foreach ($seringKritis as $sp) {
            $rasio = $sp->stok_minimum > 0
                ? round($sp->stok_tersedia / $sp->stok_minimum, 2)
                : 0;

            if ($rasio < 0.3) {
                $rekomendasi[] = [
                    'level' => 'low',
                    'title' => "Adjust min stok: {$sp->kode_material}",
                    'desc'  => "Stok {$sp->stok_tersedia}/{$sp->stok_minimum}. Riwayat menunjukkan sering kritis — pertimbangkan naikkan minimum stok.",
                ];
                break;
            }
        }
    }

    /**
     * Analisis UCL/LCL durasi pekerjaan sejenis.
     *
     * @param array &$rekomendasi
     */
    private function rekomendasiUclLcl(array &$rekomendasi): void
    {
        $jenisPekerjaan = MaintenanceReport::select('jenis', DB::raw('COUNT(*) as total'))
            ->whereNotNull('work_duration_minutes')
            ->where('work_duration_minutes', '>', 0)
            ->groupBy('jenis')
            ->having('total', '>=', 5)
            ->pluck('total', 'jenis');

        if ($jenisPekerjaan->isEmpty()) {
            return;
        }

        $rekomendasi[] = [
            'level' => 'low',
            'title' => 'Analisis UCL/LCL tersedia',
            'desc'  => 'Data maintenance sudah mencukupi untuk analisis batas durasi kerja. Buka halaman DSS untuk detail.',
        ];
    }
}