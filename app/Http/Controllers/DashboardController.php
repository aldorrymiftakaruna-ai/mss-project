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

        // Durasi Efektif Karyawan minggu ini
        $efektif = $this->hitungDurasiEfektifMingguan();
        $durasiEfektifKaryawan = $efektif['persentase'];
        $totalKaryawanAktif    = $efektif['total_karyawan'];
        $totalDurasiMingguIni  = $efektif['total_durasi_menit'];

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
            $efektif
        );

        return view('dashboard', compact(
            'totalAssets',
            'equipmentDanger',
            'laporanHariIni',
            'stokKritis',
            'durasiEfektifKaryawan',
            'totalKaryawanAktif',
            'totalDurasiMingguIni',
            'chart7Hari',
            'topEquipmentRusak',
            'cmAlerts',
            'rekomendasi',
        ));
    }

    /**
     * Hitung persentase durasi efektif kerja karyawan minggu ini.
     * Target: 40 jam (2400 menit) per karyawan aktif per minggu.
     *
     * @return array ['persentase' => float, 'total_karyawan' => int, 'total_durasi_menit' => int]
     */
    private function hitungDurasiEfektifMingguan(): array
    {
        $mulaiMinggu = Carbon::now()->startOfWeek();
        $akhirMinggu = Carbon::now()->endOfWeek();

        $totalKaryawanAktif = Employee::where('is_active', true)->count();

        $totalDurasi = (int) ManpowerLog::whereBetween('created_at', [$mulaiMinggu, $akhirMinggu])
            ->sum('durasi_menit');

        if ($totalKaryawanAktif < 1) {
            return [
                'persentase'        => 0,
                'total_karyawan'    => 0,
                'total_durasi_menit'=> $totalDurasi,
            ];
        }

        $targetTotal = $totalKaryawanAktif * 40 * 60;
        $persentase  = $targetTotal > 0
            ? round(($totalDurasi / $targetTotal) * 100, 1)
            : 0;

        return [
            'persentase'        => min($persentase, 100),
            'total_karyawan'    => $totalKaryawanAktif,
            'total_durasi_menit'=> $totalDurasi,
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
    private function generateRekomendasi(int $equipmentDanger, int $stokKritis, array $efektif): array
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

        // 4. Efektivitas jam kerja
        if ($efektif['persentase'] < 50 && $efektif['total_karyawan'] > 0) {
            $rekomendasi[] = [
                'level' => 'med',
                'title' => "Efektivitas kerja {$efektif['persentase']}% — perlu ditinjau",
                'desc'  => 'Total durasi reporting minggu ini rendah. Pastikan karyawan melakukan reporting atau investigasi penyebab.',
            ];
        } elseif ($efektif['persentase'] > 90 && $efektif['total_karyawan'] > 0) {
            $rekomendasi[] = [
                'level' => 'low',
                'title' => "Efektivitas kerja {$efektif['persentase']}% — baik",
                'desc'  => 'Produktivitas reporting minggu ini tinggi. Pertahankan.',
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