<?php

namespace App\Services\Telegram\Traits;

use App\Models\MaintenanceReport;
use Illuminate\Support\Facades\Log;

/**
 * WizardPhotoAddonTrait
 *
 * Menangani penambahan foto ke laporan yang sudah tersimpan di DB
 * menggunakan kode laporan (report_code).
 *
 * Method:
 *   - extractReportCode()     : Ekstrak kode RPT-... dari teks caption
 *   - addPhotoToReport()      : Tambah path foto ke laporan berdasarkan report_code
 *
 * CATATAN: Trait ini menggunakan model MaintenanceReport (bukan Report).
 */
trait WizardPhotoAddonTrait
{
    /**
     * Ekstrak kode laporan RPT-YYYYMMDD-XXXX dari teks.
     *
     * @param  string      $text Teks yang mungkin mengandung kode laporan
     * @return string|null       Kode laporan jika ditemukan, null jika tidak
     */
    public function extractReportCode(string $text): ?string
    {
        if (preg_match('/\bRPT-\d{8}-[A-Z0-9]{4}\b/i', $text, $m)) {
            return strtoupper($m[0]);
        }
        return null;
    }

    /**
     * Tambah path foto ke kolom photo_documentation laporan yang sudah ada.
     *
     * @param  string $reportCode Kode laporan (RPT-...)
     * @param  string $fileId     File ID Telegram atau path lokal foto
     * @return array              Respons
     */
    public function addPhotoToReport(string $reportCode, string $fileId): array
    {
        $report = MaintenanceReport::where('report_code', $reportCode)->first();
        if (!$report) {
            return $this->errorResponse('Laporan tidak ditemukan.');
        }

        $photos   = $report->photo_documentation ?? [];
        $photos[] = $fileId;
        $report->update(['photo_documentation' => $photos]);

        return [
            'message'  => 'Foto berhasil ditambahkan ke laporan ' . $reportCode,
            'keyboard' => [],
        ];
    }
}

