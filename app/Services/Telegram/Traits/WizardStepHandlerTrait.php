<?php

namespace App\Services\Telegram\Traits;

/**
 * WizardStepHandlerTrait
 *
 * Menangani logika handler untuk setiap step wizard laporan:
 *   - buildWorkDurationPrompt()       : Bangun prompt Step 3 (input durasi)
 *   - handleDurationInput()           : Proses teks durasi yang diketik teknisi
 *   - advanceToWorkDuration()         : Transisi ke Step 3 dari step sebelumnya
 *   - buildRootCausePrompt()          : Bangun prompt Step 4 (input root cause)
 *   - handleRootCauseInput()          : Proses teks root cause dari teknisi
 *   - buildPhotoDocumentationPrompt() : Bangun prompt Step 5 (foto dokumentasi)
 *   - handlePhotoCommand()            : Proses perintah teks di step foto
 *   - addPhotoToStep()                : Tambah file ID foto ke state wizard
 *   - advanceFromPhotoStep()          : Transisi keluar dari step foto ke konfirmasi
 *   - handleConfirmation()            : Proses teks konfirmasi di Step 6
 *
 * Trait ini bergantung pada method berikut dari kelas pemakai:
 *   - parseDurationToMinutes(string $text): ?int
 *   - formatDuration(int $minutes): string
 *   - equipmentLabel(array $state): string
 *   - reportTypeLabel(array $state): string
 *   - saveState(string $chatId, array $state): void
 *   - destroyWizard(string $chatId): void
 *   - buildRootCausePrompt(array $state): array  -- dipakai oleh handleDurationInput
 *   - buildPhotoDocumentationPrompt(array $state): array
 *   - buildConfirmationSummary(array $state): array
 *   - saveReport(string $chatId, array $state): array
 */
trait WizardStepHandlerTrait
{
    // =========================================================
    // STEP 3 — WAKTU PENGERJAAN
    // =========================================================

    /**
     * Transisi ke Step 3 dan tampilkan prompt durasi.
     *
     * @param  string $chatId Chat ID Telegram
     * @param  array  $state  State wizard saat ini
     * @return array  Respons
     */
    protected function advanceToWorkDuration(string $chatId, array $state): array
    {
        $state['step'] = self::STEP_WORK_DURATION;
        $this->saveState($chatId, $state);

        return $this->buildWorkDurationPrompt($state, autoDetected: !empty($state['work_duration_minutes']));
    }

    /**
     * Bangun pesan prompt Step 3 (durasi pengerjaan).
     * Jika AI sudah mendeteksi durasi, tampilkan keyboard konfirmasi.
     *
     * @param  array $state        State wizard
     * @param  bool  $autoDetected Apakah durasi sudah terdeteksi oleh AI
     * @return array Respons
     */
    protected function buildWorkDurationPrompt(array $state, bool $autoDetected = false): array
    {
        $equipmentLabel = $this->equipmentLabel($state);
        $typeLabel      = $this->reportTypeLabel($state);

        if ($autoDetected && !empty($state['work_duration_minutes'])) {
            $formatted = $this->formatDuration($state['work_duration_minutes']);
            $keyboard  = [
                ['text' => "Ya, {$formatted}", 'callback_data' => 'wizard:confirm:duration_ok'],
                ['text' => 'Ubah Durasi',      'callback_data' => 'wizard:confirm:duration_change'],
            ];
            return [
                'message' => "Laporan *{$typeLabel}* untuk *{$equipmentLabel}* diterima.\n\n" .
                    "Durasi pekerjaan terdeteksi: *{$formatted}*\n" .
                    "Sudah sesuai?",
                'keyboard' => $keyboard,
            ];
        }

        return [
            'message'  => "Equipment dikunci: *{$equipmentLabel}*\n" .
                "Jenis: *{$typeLabel}*\n\n" .
                "*Step 3/6* — Berapa lama pekerjaan berlangsung?\n" .
                "Ketik durasi (contoh: `2 jam`, `30 menit`, `1.5 jam`)",
            'keyboard' => [],
        ];
    }

    /**
     * Proses teks durasi yang diketik teknisi di Step 3.
     *
     * @param  string $chatId Chat ID Telegram
     * @param  string $text   Input teks dari teknisi
     * @param  array  $state  State wizard saat ini
     * @return array  Respons
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
    // STEP 4 — ROOT CAUSE
    // =========================================================

    /**
     * Bangun pesan prompt Step 4 (root cause / catatan).
     * Jika AI sudah mendeteksi root cause, tampilkan keyboard konfirmasi.
     *
     * @param  array $state State wizard
     * @return array Respons
     */
    protected function buildRootCausePrompt(array $state): array
    {
        $equipmentLabel = $this->equipmentLabel($state);
        $duration       = $this->formatDuration($state['work_duration_minutes'] ?? 0);

        if (!empty($state['root_cause'])) {
            $existing = $state['root_cause'];
            return [
                'message'  => "*Step 4/6* — Catatan / Root Cause\n\n" .
                    "Root cause yang terdeteksi dari laporan:\n_{$existing}_\n\n" .
                    "Gunakan catatan ini atau ketik yang baru:",
                'keyboard' => [
                    ['text' => 'Gunakan ini', 'callback_data' => 'wizard:confirm:rootcause_ok'],
                    ['text' => 'Ubah',        'callback_data' => 'wizard:confirm:rootcause_change'],
                ],
            ];
        }

        return [
            'message'  => "*Step 4/6* — Catatan / Root Cause\n\n" .
                "Equipment: *{$equipmentLabel}*\n" .
                "Durasi: *{$duration}*\n\n" .
                "Ketik *catatan* (penyebab kerusakan/pekerjaan):",
            'keyboard' => [],
        ];
    }

    /**
     * Proses teks root cause yang diketik teknisi di Step 4.
     *
     * @param  string $chatId Chat ID Telegram
     * @param  string $text   Input teks dari teknisi
     * @param  array  $state  State wizard saat ini
     * @return array  Respons
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
    // STEP 5 — FOTO DOKUMENTASI
    // =========================================================

    /**
     * Bangun pesan prompt Step 5 (foto dokumentasi).
     * Menyesuaikan pesan berdasarkan jumlah foto yang sudah masuk
     * dan apakah ada foto awal dari Step 1.
     *
     * @param  array $state State wizard
     * @return array Respons
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
                'message'  => "*Step 5/6* — Foto Dokumentasi\n\n" .
                    "{$label}Tambah foto lagi, atau lanjutkan?",
                'keyboard' => [
                    ['text' => 'Cukup, Lanjutkan',  'callback_data' => 'wizard:confirm:photo_doc_done'],
                    ['text' => 'Tambah Foto Lagi',  'callback_data' => 'wizard:confirm:photo_doc_more'],
                    ['text' => 'Skip (Tanpa Foto)', 'callback_data' => 'wizard:confirm:photo_doc_skip'],
                ],
            ];
        }

        if ($currentPhotos > 0) {
            return [
                'message'  => "*Step 5/6* — Foto Dokumentasi\n\n" .
                    "{$currentPhotos} foto sudah diterima.\n" .
                    "Kirim foto lagi, atau lanjutkan:",
                'keyboard' => [
                    ['text' => 'Cukup, Lanjutkan', 'callback_data' => 'wizard:confirm:photo_doc_done'],
                    ['text' => 'Skip Sisa Foto',   'callback_data' => 'wizard:confirm:photo_doc_skip'],
                ],
            ];
        }

        return [
            'message'  => "*Step 5/6* — Foto Dokumentasi\n\n" .
                "Kirim foto dokumentasi pekerjaan (opsional, bisa lebih dari 1).\n" .
                "Atau skip jika tidak ada:",
            'keyboard' => [
                ['text' => 'Skip (Tanpa Foto)', 'callback_data' => 'wizard:confirm:photo_doc_skip'],
            ],
        ];
    }

    // =========================================================
    // HANDLER FOTO (STEP 5)
    // =========================================================

    /**
     * Proses perintah teks di step foto ("selesai", "skip", dll).
     * Dipanggil dari handleTextInput ketika step adalah foto.
     *
     * @param  string $chatId    Chat ID Telegram
     * @param  string $text      Input teks dari teknisi
     * @param  array  $state     State wizard saat ini
     * @param  string $photoStep Tipe step: 'documentation'
     * @return array  Respons
     */
    protected function handlePhotoCommand(string $chatId, string $text, array $state, string $photoStep): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['selesai', 'done', 'lanjut', 'skip', 'next'])) {
            return $this->advanceFromPhotoStep($chatId, $state, $photoStep);
        }

        $currentCount = count($state['photo_documentation'] ?? []);

        return [
            'message'  => "*Step 5/6* — {$currentCount} foto diterima.\n" .
                "Kirim foto berikutnya, atau ketik *selesai* untuk lanjut.",
            'keyboard' => [
                ['text' => 'Selesai, Lanjutkan', 'callback_data' => 'wizard:confirm:photo_doc_done'],
                ['text' => 'Skip',               'callback_data' => 'wizard:confirm:photo_doc_skip'],
            ],
        ];
    }

    /**
     * Tambah file ID foto ke state wizard (dipanggil dari handlePhotoInput).
     *
     * @param  string $chatId    Chat ID Telegram
     * @param  string $fileId    File ID foto dari Telegram
     * @param  array  $state     State wizard saat ini
     * @param  string $photoStep Tipe step: 'documentation'
     * @return array  Respons
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
            ],
        ];
    }

    /**
     * Transisi keluar dari step foto ke Step 6 (konfirmasi).
     * Jika ada foto awal dari Step 1, di-prepend ke photo_documentation.
     *
     * @param  string $chatId    Chat ID Telegram
     * @param  array  $state     State wizard saat ini
     * @param  string $photoStep Tipe step yang sedang diselesaikan ('documentation')
     * @return array  Respons
     */
    protected function advanceFromPhotoStep(string $chatId, array $state, string $photoStep): array
    {
        if ($photoStep === 'documentation' && !empty($state['initial_photo_file_id'])) {
            if (empty($state['photo_documentation'])) {
                $state['photo_documentation'] = [];
            }
            // Prepend foto awal jika belum masuk ke array
            if (!in_array($state['initial_photo_file_id'], $state['photo_documentation'])) {
                array_unshift($state['photo_documentation'], $state['initial_photo_file_id']);
            }
        }

        // Dari foto dokumentasi langsung ke konfirmasi
        $state['step'] = self::STEP_CONFIRMATION;
        $this->saveState($chatId, $state);
        return $this->buildConfirmationSummary($state);
    }

    // =========================================================
    // STEP 6 — KONFIRMASI (handler teks)
    // =========================================================

    /**
     * Proses teks konfirmasi ("ya"/"tidak") yang diketik teknisi di Step 6.
     *
     * @param  string $chatId Chat ID Telegram
     * @param  string $text   Input teks dari teknisi
     * @param  array  $state  State wizard saat ini
     * @return array  Respons
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

        // Input tidak dikenali — tampilkan ulang ringkasan konfirmasi
        return $this->buildConfirmationSummary($state);
    }
}
