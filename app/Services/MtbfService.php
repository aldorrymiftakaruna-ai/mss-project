<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MaintenanceReport;
use Carbon\Carbon;

class MtbfService
{
    /**
     * Hitung MTBF untuk satu asset dengan metode Running MTBF.
     *
     * Logic:
     * - Ambil semua laporan corrective, urut ascending.
     * - Hitung selisih hari antar laporan berurutan (interval historis).
     * - Tambahkan current run time: (hari ini - tanggal laporan corrective terakhir).
     * - MTBF = total semua interval / (jumlah interval historis + 1).
     *
     * Edge cases:
     * - 1 laporan corrective -> MTBF = (hari ini - tanggal laporan tsb), flag data_terbatas = true.
     * - 0 laporan corrective -> return null.
     *
     * @param Asset $asset
     * @return object { mtbf_hari, total_laporan, first_report, last_report, data_terbatas, ada_data }
     */
    public function hitung(Asset $asset): object
    {
        $tanggalLaporan = MaintenanceReport::where('asset_id', $asset->id)
            ->where('jenis', 'corrective')
            ->whereNotNull('created_at')
            ->orderBy('created_at')
            ->pluck('created_at');

        $totalLaporan = $tanggalLaporan->count();
        $hariIni      = Carbon::today();

        // Kasus: tidak ada laporan corrective sama sekali
        if ($totalLaporan === 0) {
            return (object) [
                'mtbf_hari'     => null,
                'total_laporan'  => 0,
                'first_report'   => null,
                'last_report'    => null,
                'data_terbatas'  => false,
                'ada_data'       => false,
            ];
        }

        $tanggalTerakhir = $tanggalLaporan->last();

        // Kasus: hanya 1 laporan corrective
        if ($totalLaporan === 1) {
            $selisih = $tanggalTerakhir->diffInDays($hariIni);
            return (object) [
                'mtbf_hari'     => round($selisih, 1),
                'total_laporan'  => 1,
                'first_report'   => $tanggalLaporan->first(),
                'last_report'    => $tanggalTerakhir,
                'data_terbatas'  => true,
                'ada_data'       => true,
            ];
        }

        // Kasus: >= 2 laporan corrective
        $totalSelisih   = 0;
        $jumlahInterval = 0;
        $prev = null;

        foreach ($tanggalLaporan as $tgl) {
            if ($prev !== null) {
                $totalSelisih += $prev->diffInDays($tgl);
                $jumlahInterval++;
            }
            $prev = $tgl;
        }

        // Tambahkan current run time: hari ini - tanggal terakhir
        $totalSelisih += $tanggalTerakhir->diffInDays($hariIni);
        $jumlahInterval++; // +1 untuk current run time

        $mtbfHari = $jumlahInterval > 0
            ? round($totalSelisih / $jumlahInterval, 1)
            : 0;

        return (object) [
            'mtbf_hari'     => $mtbfHari,
            'total_laporan'  => $totalLaporan,
            'first_report'   => $tanggalLaporan->first(),
            'last_report'    => $tanggalTerakhir,
            'data_terbatas'  => false,
            'ada_data'       => true,
        ];
    }

    /**
     * Hitung MTBF dalam hari untuk keperluan numerik (contoh: matriks TOPSIS).
     * Mengembalikan float (0 jika tidak ada data corrective).
     *
     * @param Asset $asset
     * @return float
     */
    public function hitungNumerik(Asset $asset): float
    {
        $result = $this->hitung($asset);

        if (!$result->ada_data || $result->mtbf_hari === null) {
            return 0;
        }

        return (float) $result->mtbf_hari;
    }
}
