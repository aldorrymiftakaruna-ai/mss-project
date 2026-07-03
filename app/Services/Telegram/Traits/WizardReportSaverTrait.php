<?php

namespace App\Services\Telegram\Traits;

use App\Models\MaintenanceReport;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;

/**
 * WizardReportSaverTrait
 *
 * Menangani penyimpanan laporan ke database dan helper terkait foto:
 *   - saveReport()                  : Simpan laporan ke DB setelah konfirmasi Step 6
 *   - buildConfirmationSummary()    : Bangun pesan ringkasan Step 6
 *   - generateReportCode()          : Generate kode RPT-YYYYMMDD-XXXX unik per hari
 *   - isValidLocalPhotoPath()       : Validasi apakah nilai adalah path lokal foto
 *   - filterValidLocalPhotoPaths()  : Filter array foto, buang yang bukan path lokal
 *   - findEmployeeByTelegramId()    : Cari Employee berdasarkan telegram_id dari chat
 *
 * Trait ini bergantung pada method berikut dari kelas pemakai:
 *   - equipmentLabel(array $state): string
 *   - formatDuration(int $minutes): string
 *   - destroyWizard(string $chatId): void
 *   - errorResponse(string $message): array
 *
 * PEMETAAN STATUS WIZARD KE STATUS MAINTENANCE_REPORTS:
 *   Wizard 'draft'         -> maintenance_reports.status = 'open'
 *   Wizard 'needs_review'  -> maintenance_reports.status = 'on_progress'
 *   Wizard 'completed'     -> maintenance_reports.status = 'done'
 *
 * CATATAN: Trait ini menggunakan model MaintenanceReport (bukan Report).
 * Teknisi dicari dari tabel Employee melalui telegram_id (bukan model
 * Technician terpisah).
 */
trait WizardReportSaverTrait
{
    // =========================================================
    // STEP 6 — KONFIRMASI & SIMPAN
    // =========================================================

    /**
     * Bangun pesan ringkasan konfirmasi untuk Step 6.
     * Menampilkan semua data yang akan disimpan agar teknisi bisa verifikasi.
     *
     * @param  array $state State wizard
     * @return array Respons dengan pesan ringkasan dan keyboard Ya/Batalkan
     */
    protected function buildConfirmationSummary(array $state): array
    {
        // Guard clause: jika tidak ada equipment terpilih, tolak lanjut ke konfirmasi
        if (empty($state['equipment_id'])) {
            return [
                'message'  => 'Equipment belum dipilih. Laporan tidak dapat dilanjutkan. Silakan mulai ulang dengan menyebutkan kode equipment yang valid.',
                'keyboard' => [],
            ];
        }

        $equipmentLabel = $this->equipmentLabel($state);
        $duration       = $this->formatDuration($state['work_duration_minutes'] ?? 0);
        $rootCause      = $state['root_cause'] ?? '-';
        $photoDocCount  = count($state['photo_documentation'] ?? []);

        $msg  = "*Step 6/6* — Konfirmasi Laporan\n\n";
        $msg .= "Periksa ringkasan berikut sebelum disimpan:\n\n";
        $msg .= "*Equipment* : {$equipmentLabel}\n";
        $msg .= "*Durasi*    : {$duration}\n";
        $msg .= "*Root Cause*: {$rootCause}\n";
        $msg .= "*Foto*      : {$photoDocCount} foto\n\n";
        $msg .= "Simpan laporan ini?";

        return [
            'message'  => $msg,
            'keyboard' => [
                ['text' => 'Ya, Simpan', 'callback_data' => 'wizard:confirm:save_report'],
                ['text' => 'Batalkan',   'callback_data' => 'wizard:confirm:cancel_report'],
            ],
        ];
    }

    /**
     * Simpan laporan ke tabel maintenance_reports setelah teknisi mengonfirmasi.
     *
     * Alur:
     *   1. Cari Employee berdasarkan telegram_id dari chatId
     *   2. Generate report_code unik
     *   3. Filter foto: hanya path lokal valid dari PhotoStorageService
     *   4. Simpan ke MaintenanceReport dengan field mapping:
     *      - deskripsi_masalah <- state['text']
     *      - reported_by       <- employee->id (FK ke employees)
     *      - tanggal           <- now()
     *      - jenis             <- 'corrective' atau 'preventive'
     *      - photo_documentation <- array path foto (json)
     *      - status            <- 'open' (default untuk draft wizard baru)
     *   5. Hancurkan sesi wizard
     *
     * @param  string $chatId Chat ID Telegram
     * @param  array  $state  State wizard saat ini
     * @return array  Respons sukses atau error
     */
    protected function saveReport(string $chatId, array $state): array
    {
        try {
            $employee = $this->findEmployeeByTelegramId($chatId);
            if (!$employee) {
                return $this->errorResponse(
                    "Akun teknisi tidak ditemukan untuk chat ini.\n" .
                    "Hubungi admin untuk mendaftarkan Telegram ID kamu."
                );
            }

            $reportCode = $this->generateReportCode();

            // Tentukan jenis laporan
            $jenis = 'corrective';
            if (!empty($state['report_type']) && $state['report_type'] === 'preventive') {
                $jenis = 'preventive';
            }

            // Filter foto: hanya path lokal hasil PhotoStorageService->store()
            $photoDocumentation = $this->filterValidLocalPhotoPaths($state['photo_documentation'] ?? []);

            // AI suggestion jika ada analisis AI
            $aiSuggestion = null;
            if (!empty($state['ai_analysis'])) {
                $aiSuggestion = $state['ai_analysis'];
            }

            $report = MaintenanceReport::create([
                'report_code'           => $reportCode,
                'asset_id'              => $state['equipment_id'] ?? null,
                'reported_by'           => $employee->id,
                'shift'                 => $state['shift'] ?? $employee->shift ?? 'reguler',
                'tanggal'               => now()->toDateString(),
                'jenis'                 => $jenis,
                'deskripsi_masalah'     => $state['text'],
                'tindakan'              => $state['root_cause'] ?? null,
                'work_duration_minutes' => $state['work_duration_minutes'] ?? null,
                'root_cause'            => $state['root_cause'] ?? null,
                'photo_documentation'   => $photoDocumentation,
                'wizard_started_at'     => $state['created_at'] ?? null,
                'submitted_at'          => now(),
                'ai_suggestion_json'    => $aiSuggestion,
                'status'                => 'open',
                'catatan'               => null,
            ]);

            $this->destroyWizard($chatId);

            Log::info("WizardService: Laporan tersimpan untuk chat {$chatId}", [
                'report_id'       => $report->id,
                'report_code'     => $reportCode,
                'employee_id'     => $employee->id,
                'photo_doc_count' => count($photoDocumentation),
            ]);

            $equipmentLabel = $this->equipmentLabel($state);
            $duration       = $this->formatDuration($state['work_duration_minutes'] ?? 0);

            $msg  = "*Laporan Berhasil Disimpan!*\n\n";
            $msg .= "Kode Laporan: `{$reportCode}`\n";
            $msg .= "Equipment: {$equipmentLabel}\n";
            $msg .= "Durasi: {$duration}\n\n";
            $msg .= "Terima kasih, laporan sudah masuk ke sistem.";

            return [
                'message'     => $msg,
                'keyboard'    => [],
                'report_code' => $reportCode,
                'report_id'   => $report->id,
                'saved'       => true,
            ];
        } catch (\Throwable $e) {
            Log::error("WizardService: Gagal menyimpan laporan untuk chat {$chatId}", [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                "Terjadi kesalahan saat menyimpan laporan. Silakan coba lagi atau hubungi admin.\n" .
                "Error: " . $e->getMessage()
            );
        }
    }

    // =========================================================
    // HELPER KODE LAPORAN, EMPLOYEE, & VALIDASI FOTO
    // =========================================================

    /**
     * Cari Employee berdasarkan telegram_id.
     * chatId dari Telegram adalah integer/string yang cocok dengan
     * kolom telegram_id (bigint) di tabel employees.
     *
     * @param  string        $chatId Telegram chat ID (user ID)
     * @return Employee|null
     */
    protected function findEmployeeByTelegramId(string $chatId): ?Employee
    {
        return Employee::where('telegram_id', $chatId)->where('is_active', true)->first();
    }

    /**
     * Generate kode laporan RPT-YYYYMMDD-XXXX.
     * XXXX adalah string acak 4 karakter alfanumerik unik.
     *
     * @return string Kode laporan baru
     */
    protected function generateReportCode(): string
    {
        $prefix = 'RPT-' . now()->format('Ymd') . '-';

        do {
            $code = $prefix . strtoupper(\Illuminate\Support\Str::random(4));
        } while (MaintenanceReport::where('report_code', $code)->exists());

        return $code;
    }

    /**
     * Cek apakah sebuah nilai foto adalah path lokal hasil PhotoStorageService->store().
     * Path lokal selalu mengandung tanda "/" (format: reports/YYYY/MM/DD/{chat_id}/{filename}.jpg).
     * File ID Telegram asli tidak pernah mengandung "/".
     *
     * @param  mixed $value Nilai yang akan dicek
     * @return bool
     */
    protected function isValidLocalPhotoPath(mixed $value): bool
    {
        return is_string($value) && $value !== '' && str_contains($value, '/');
    }

    /**
     * Filter array foto agar hanya berisi path lokal yang valid.
     * Entri yang bukan path lokal (misal file_id Telegram mentah yang lolos
     * tanpa diproses PhotoStorageService) dibuang dan dicatat ke log.
     *
     * @param  array $photos Array path atau file_id foto
     * @return array Array yang hanya berisi path lokal valid
     */
    protected function filterValidLocalPhotoPaths(array $photos): array
    {
        $valid   = [];
        $invalid = [];

        foreach ($photos as $photo) {
            if ($this->isValidLocalPhotoPath($photo)) {
                $valid[] = $photo;
            } else {
                $invalid[] = $photo;
            }
        }

        if (!empty($invalid)) {
            Log::warning('WizardService: Foto tidak valid dibuang saat saveReport (bukan path lokal)', [
                'invalid_count' => count($invalid),
                'invalid_items' => $invalid,
            ]);
        }

        return $valid;
    }
}
