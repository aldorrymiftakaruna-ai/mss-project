<?php

namespace App\Services\Telegram\Traits;

/**
 * WizardStepHandlerTrait
 *
 * Menangani logika handler untuk setiap step wizard laporan.
 * Step baru: catatan/feedback setelah foto.
 *
 * Alur lengkap:
 *   1. Equipment
 *   2. Shift
 *   3. Jenis
 *   4. Status
 *   5. Durasi
 *   6. Root Cause
 *   7. Foto
 *   8. Catatan/Feedback
 *   9. Konfirmasi
 *
 * Trait ini bergantung pada method berikut dari kelas pemakai:
 *   - parseDurationToMinutes(string $text): ?int
 *   - formatDuration(int $minutes): string
 *   - equipmentLabel(array $state): string
 *   - reportTypeLabel(array $state): string
 *   - saveState(string $chatId, array $state): void
 *   - destroyWizard(string $chatId): void
 *   - buildRootCausePrompt(array $state): array
 *   - buildPhotoDocumentationPrompt(array $state): array
 *   - buildConfirmationSummary(array $state): array
 *   - saveReport(string $chatId, array $state): array
 */
trait WizardStepHandlerTrait
{
    // =========================================================
    // STEP DURASI
    // =========================================================

    /**
     * Transisi ke Step Durasi.
     *
     * @param  string $chatId
     * @param  array  $state
     * @return array
     */
    protected function advanceToWorkDuration(string $chatId, array $state): array
    {
        $state['step'] = self::STEP_WORK_DURATION;
        $this->saveState($chatId, $state);

        return $this->buildWorkDurationPrompt($state, autoDetected: !empty($state['work_duration_minutes']));
    }

    /**
     * Bangun pesan prompt durasi.
     *
     * @param  array $state
     * @param  bool  $autoDetected
     * @return array
     */
    protected function buildWorkDurationPrompt(array $state, bool $autoDetected = false): array
    {
        $equipmentLabel = $this->equipmentLabel($state);

        if ($autoDetected && !empty($state['work_duration_minutes'])) {
            $formatted = $this->formatDuration($state['work_duration_minutes']);
            $keyboard  = [
                ['text' => "Ya, {$formatted}", 'callback_data' => 'wizard:confirm:duration_ok'],
                ['text' => 'Ubah Durasi',      'callback_data' => 'wizard:confirm:duration_change'],
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ];
            return [
                'message' => "Laporan untuk *{$equipmentLabel}* diterima.\n\n" .
                    "Durasi pekerjaan terdeteksi: *{$formatted}*\n" .
                    "Sudah sesuai?",
                'keyboard' => $keyboard,
            ];
        }

        return [
            'message'  => "Equipment: *{$equipmentLabel}*\n\n" .
                "*Step 5/11* — Berapa lama pekerjaan berlangsung?\n" .
                "Ketik durasi (contoh: `2 jam`, `30 menit`, `1.5 jam`)",
            'keyboard' => [
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses teks durasi.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleDurationInput(string $chatId, string $text, array $state): array
    {
        $minutes = $this->parseDurationToMinutes($text);

        if ($minutes === null || $minutes <= 0) {
            return [
                'message'  => "Durasi tidak dikenali. Coba format lain:\n" .
                    "`2 jam`, `30 menit`, `1 jam 30 menit`, `90 menit`",
                'keyboard' => [],
            ];
        }

        $state['work_duration_minutes'] = $minutes;
        $state['step']                  = self::STEP_ROOT_CAUSE;
        $this->saveState($chatId, $state);

        return $this->buildRootCausePrompt($state);
    }

    // =========================================================
    // STEP ROOT CAUSE
    // =========================================================

    /**
     * Bangun pesan prompt root cause.
     *
     * @param  array $state
     * @return array
     */
    protected function buildRootCausePrompt(array $state): array
    {
        $equipmentLabel = $this->equipmentLabel($state);
        $duration       = $this->formatDuration($state['work_duration_minutes'] ?? 0);
        $shift          = $state['shift'] ?? '-';
        $typeLabel      = $this->reportTypeLabel($state);
        $statusLabel    = ($state['status'] ?? 'belum_selesai') === 'selesai' ? 'Selesai' : 'Belum Selesai';

        if (!empty($state['root_cause'])) {
            $existing = $state['root_cause'];
            return [
                'message'  => "*Step 6/11* — Root Cause\n\n" .
                    "Root cause yang terdeteksi dari laporan:\n_{$existing}_\n\n" .
                    "Gunakan catatan ini atau ketik yang baru:",
                'keyboard' => [
                    ['text' => 'Gunakan ini', 'callback_data' => 'wizard:confirm:rootcause_ok'],
                    ['text' => 'Ubah',        'callback_data' => 'wizard:confirm:rootcause_change'],
                    ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
                ],
            ];
        }

        return [
            'message'  => "*Step 6/11* — Root Cause\n\n" .
                "Equipment: *{$equipmentLabel}*  |  Shift: *{$shift}*\n" .
                "Jenis: *{$typeLabel}*  |  Status: *{$statusLabel}*\n" .
                "Durasi: *{$duration}*\n\n" .
                "Ketik *root cause* (penyebab kerusakan / pekerjaan yang dilakukan):",
            'keyboard' => [],
        ];
    }

    /**
     * Proses teks root cause.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleRootCauseInput(string $chatId, string $text, array $state): array
    {
        $trimmed = trim($text);

        if (mb_strlen($trimmed) < self::ROOT_CAUSE_MIN_LENGTH) {
            return [
                'message'  => "Catatan terlalu pendek (minimal " . self::ROOT_CAUSE_MIN_LENGTH . " karakter).\n" .
                    "Deskripsikan penyebab kerusakan/pekerjaan:",
                'keyboard' => [],
            ];
        }

        $state['root_cause'] = $trimmed;
        $state['step']       = self::STEP_PHOTO_DOCUMENTATION;
        $this->saveState($chatId, $state);

        return $this->buildPhotoDocumentationPrompt($state);
    }

    // =========================================================
    // STEP FOTO DOKUMENTASI
    // =========================================================

    /**
     * Bangun pesan prompt foto dokumentasi.
     *
     * @param  array $state
     * @return array
     */
    protected function buildPhotoDocumentationPrompt(array $state): array
    {
        $hasInitialPhoto = !empty($state['initial_photo_file_id']);
        $currentPhotos   = count($state['photo_documentation'] ?? []);

        $label = $hasInitialPhoto && $currentPhotos === 0
            ? "Sudah ada 1 foto yang dikirim bersama laporan awal.\n"
            : '';

        if ($hasInitialPhoto && $currentPhotos === 0) {
            return [
                'message'  => "*Step 7/11* — Foto Dokumentasi\n\n" .
                    "{$label}Tambah foto lagi, atau lanjutkan?",
                'keyboard' => [
                    ['text' => 'Cukup, Lanjutkan',  'callback_data' => 'wizard:confirm:photo_doc_done'],
                    ['text' => 'Tambah Foto Lagi',  'callback_data' => 'wizard:confirm:photo_doc_more'],
                    ['text' => 'Skip (Tanpa Foto)', 'callback_data' => 'wizard:confirm:photo_doc_skip'],
                    ['text' => 'Batalkan Laporan',  'callback_data' => 'wizard:cancel_wizard'],
                ],
            ];
        }

        if ($currentPhotos > 0) {
            return [
                'message'  => "*Step 7/11* — Foto Dokumentasi\n\n" .
                    "{$currentPhotos} foto sudah diterima.\n" .
                    "Kirim foto lagi, atau lanjutkan:",
                'keyboard' => [
                    ['text' => 'Cukup, Lanjutkan', 'callback_data' => 'wizard:confirm:photo_doc_done'],
                    ['text' => 'Skip Sisa Foto',   'callback_data' => 'wizard:confirm:photo_doc_skip'],
                    ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
                ],
            ];
        }

        return [
            'message'  => "*Step 7/11* — Foto Dokumentasi\n\n" .
                "Kirim foto dokumentasi pekerjaan (opsional, bisa lebih dari 1).\n" .
                "Atau skip jika tidak ada:",
            'keyboard' => [
                ['text' => 'Skip (Tanpa Foto)', 'callback_data' => 'wizard:confirm:photo_doc_skip'],
                ['text' => 'Batalkan Laporan',  'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses perintah teks di step foto.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @param  string $photoStep
     * @return array
     */
    protected function handlePhotoCommand(string $chatId, string $text, array $state, string $photoStep): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['selesai', 'done', 'lanjut', 'skip', 'next'])) {
            return $this->advanceFromPhotoStep($chatId, $state, $photoStep);
        }

        $currentCount = count($state['photo_documentation'] ?? []);

        return [
            'message'  => "*Step 7/11* — {$currentCount} foto diterima.\n" .
                "Kirim foto berikutnya, atau ketik *selesai* untuk lanjut.",
            'keyboard' => [
                ['text' => 'Selesai, Lanjutkan', 'callback_data' => 'wizard:confirm:photo_doc_done'],
                ['text' => 'Skip',               'callback_data' => 'wizard:confirm:photo_doc_skip'],
                ['text' => 'Batalkan Laporan',   'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Tambah foto ke state wizard.
     *
     * @param  string $chatId
     * @param  string $fileId
     * @param  array  $state
     * @param  string $photoStep
     * @return array
     */
    protected function addPhotoToStep(string $chatId, string $fileId, array $state, string $photoStep): array
    {
        $key          = 'photo_documentation';
        $state[$key]  = $state[$key] ?? [];
        $state[$key][] = $fileId;
        $count        = count($state[$key]);
        $this->saveState($chatId, $state);

        return [
            'message'  => "Foto {$count} diterima.\n" .
                "Kirim foto berikutnya, atau tekan *Selesai* untuk lanjut.",
            'keyboard' => [
                ['text' => 'Selesai, Lanjutkan', 'callback_data' => 'wizard:confirm:photo_doc_done'],
                ['text' => 'Skip Sisa',           'callback_data' => 'wizard:confirm:photo_doc_skip'],
                ['text' => 'Batalkan Laporan',   'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Transisi keluar dari step foto ke step catatan/feedback.
     *
     * @param  string $chatId
     * @param  array  $state
     * @param  string $photoStep
     * @return array
     */
    protected function advanceFromPhotoStep(string $chatId, array $state, string $photoStep): array
    {
        if ($photoStep === 'documentation' && !empty($state['initial_photo_file_id'])) {
            if (empty($state['photo_documentation'])) {
                $state['photo_documentation'] = [];
            }
            if (!in_array($state['initial_photo_file_id'], $state['photo_documentation'])) {
                array_unshift($state['photo_documentation'], $state['initial_photo_file_id']);
            }
        }

        // Lanjut ke step catatan, bukan langsung konfirmasi
        $state['step'] = self::STEP_CATATAN;
        $this->saveState($chatId, $state);
        return $this->buildCatatanPrompt($state);
    }

    // =========================================================
    // STEP CATATAN / FEEDBACK
    // =========================================================

    /**
     * Bangun prompt untuk menanyakan catatan/feedback.
     *
     * @param  array $state
     * @return array
     */
    protected function buildCatatanPrompt(array $state): array
    {
        return [
            'message'  => "*Step 8/11* — Catatan / Feedback\n\n" .
                "Adakah catatan atau feedback terhadap pekerjaan ini?\n" .
                "Ketik catatan, atau skip jika tidak ada.",
            'keyboard' => [
                ['text' => 'Skip (Tanpa Catatan)', 'callback_data' => 'wizard:confirm:catatan_skip'],
                ['text' => 'Batalkan Laporan',     'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses input catatan/feedback dari teknisi.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleCatatanInput(string $chatId, string $text, array $state): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['skip', 'lewat', 'tidak', 'none', '-'])) {
            return $this->advanceFromCatatan($chatId, $state);
        }

        $state['catatan'] = trim($text);
        return $this->advanceFromCatatan($chatId, $state);
    }

    /**
     * Simpan catatan (atau null jika skip) lalu lanjut ke step downtime.
     *
     * @param  string $chatId
     * @param  array  $state
     * @return array
     */
    protected function advanceFromCatatan(string $chatId, array $state): array
    {
        $state['step'] = self::STEP_DOWNTIME;
        $this->saveState($chatId, $state);
        return $this->buildDowntimePrompt($state);
    }

    // =========================================================
    // STEP DOWNTIME
    // =========================================================

    /**
     * Bangun prompt untuk menanyakan downtime.
     * Input angka menit, seperti durasi pekerjaan.
     *
     * @param  array $state
     * @return array
     */
    protected function buildDowntimePrompt(array $state): array
    {
        return [
            'message'  => "*Step 9/11* — Downtime\n\n" .
                "Apakah ada downtime (mesin berhenti) akibat pekerjaan ini?\n" .
                "Ketik jumlah menit, atau *0* / *skip* jika tidak ada.\n\n" .
                "Contoh: `30`, `120`, `0`",
            'keyboard' => [
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses input downtime dari teknisi.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleDowntimeInput(string $chatId, string $text, array $state): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['skip', '0', 'tidak', 'none', '-'])) {
            $state['downtime_minutes'] = 0;
            return $this->advanceToOvertime($chatId, $state);
        }

        if (is_numeric($text) && (int) $text >= 0) {
            $state['downtime_minutes'] = (int) $text;
            return $this->advanceToOvertime($chatId, $state);
        }

        return [
            'message'  => "Masukkan angka menit downtime (contoh: `30`, `120`),\n" .
                "atau ketik *0* jika tidak ada downtime.",
            'keyboard' => [
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Simpan downtime dan lanjut ke step lembur.
     *
     * @param  string $chatId
     * @param  array  $state
     * @return array
     */
    protected function advanceToOvertime(string $chatId, array $state): array
    {
        $state['step'] = self::STEP_OVERTIME;
        $this->saveState($chatId, $state);
        return $this->buildOvertimePrompt($state);
    }

    // =========================================================
    // STEP OVERTIME
    // =========================================================

    /**
     * Bangun prompt untuk menanyakan jam lembur.
     * Input angka jam — 0 / skip berarti tidak lembur.
     *
     * @param  array $state
     * @return array
     */
    protected function buildOvertimePrompt(array $state): array
    {
        return [
            'message'  => "*Step 10/11* — Lembur\n\n" .
                "Berapa jam lembur yang dilakukan?\n" .
                "Ketik *0* atau *skip* jika tidak lembur.\n\n" .
                "Contoh: `1`, `2.5`, `4`, `0`",
            'keyboard' => [
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses input jam lembur.
     * 0 / skip berarti tidak lembur.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleOvertimeInput(string $chatId, string $text, array $state): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['skip', '0', 'tidak', 'none', '-'])) {
            $state['is_overtime'] = false;
            $state['overtime_hours'] = 0;
            return $this->advanceFromOvertime($chatId, $state);
        }

        if (is_numeric($text) && (float) $text > 0 && (float) $text <= 24) {
            $state['is_overtime'] = true;
            $state['overtime_hours'] = (float) $text;
            return $this->advanceFromOvertime($chatId, $state);
        }

        return [
            'message'  => "Masukkan jam lembur (angka, maks 24 jam).\n" .
                "Ketik *0* jika tidak lembur.\n\n" .
                "Contoh: `1`, `2.5`, `4`, `0`",
            'keyboard' => [
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Simpan data lembur dan lanjut ke konfirmasi.
     *
     * @param  string $chatId
     * @param  array  $state
     * @return array
     */
    protected function advanceFromOvertime(string $chatId, array $state): array
    {
        $state['step'] = self::STEP_CONFIRMATION;
        $this->saveState($chatId, $state);
        return $this->buildConfirmationSummary($state);
    }

    // =========================================================
    // STEP KONFIRMASI
    // =========================================================

    /**
     * Proses teks konfirmasi ("ya"/"tidak").
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleConfirmation(string $chatId, string $text, array $state): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['ya', 'yes', 'ok', 'oke', 'simpan', 'confirm'])) {
            return $this->saveReport($chatId, $state);
        }

        if (in_array($text, ['tidak', 'no', 'batal', 'cancel', 'batalkan'])) {
            $this->destroyWizard($chatId);
            return [
                'message'  => "Laporan dibatalkan. Wizard ditutup.\nKirim laporan baru kapan saja.",
                'keyboard' => [],
            ];
        }

        return $this->buildConfirmationSummary($state);
    }
}
