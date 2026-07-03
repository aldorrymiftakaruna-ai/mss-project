<?php

namespace App\Services\Telegram\Traits;

/**
 * WizardNewStepsTrait
 *
 * Step baru yang ditambahkan setelah pemilihan equipment:
 *   - advanceAfterEquipment()   : Transisi ke Step Shift
 *   - buildShiftPrompt()        : Tanya shift (1/2/3/reguler)
 *   - handleShiftInput()        : Proses input shift
 *   - buildReportTypePrompt()   : Tanya corrective/preventive
 *   - handleReportTypeInput()   : Proses input jenis
 *   - buildStatusPrompt()       : Tanya selesai/belum selesai
 *   - handleStatusInput()       : Proses input status
 *   - advanceToWorkDuration()   : Setelah semua, lanjut ke durasi
 */
trait WizardNewStepsTrait
{
    // =========================================================
    // STEP SHIFT (setelah equipment)
    // =========================================================

    /**
     * Transisi ke Step Shift setelah equipment dipilih.
     *
     * @param  string $chatId
     * @param  array  $state
     * @return array
     */
    protected function advanceAfterEquipment(string $chatId, array $state): array
    {
        $state['step'] = self::STEP_SHIFT;
        $this->saveState($chatId, $state);
        return $this->buildShiftPrompt($state);
    }

    /**
     * Bangun prompt untuk memilih shift.
     *
     * @param  array $state
     * @return array
     */
    protected function buildShiftPrompt(array $state): array
    {
        $equipmentLabel = $this->equipmentLabel($state);

        return [
            'message' => "Equipment: *{$equipmentLabel}*\n\n" .
                "Kamu *shift* berapa saat mengerjakan ini?",
            'keyboard' => [
                ['text' => 'Shift 1 (08-16)', 'callback_data' => 'wizard:confirm:shift_1'],
                ['text' => 'Shift 2 (16-24)', 'callback_data' => 'wizard:confirm:shift_2'],
                ['text' => 'Shift 3 (00-08)', 'callback_data' => 'wizard:confirm:shift_3'],
                ['text' => 'Shift Reguler',   'callback_data' => 'wizard:confirm:shift_reguler'],
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses input teks shift (jika teknisi mengetik manual).
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleShiftInput(string $chatId, string $text, array $state): array
    {
        $text = strtolower(trim($text));
        $shift = null;

        if (in_array($text, ['1', 'shift 1', 'satu'])) $shift = '1';
        elseif (in_array($text, ['2', 'shift 2', 'dua'])) $shift = '2';
        elseif (in_array($text, ['3', 'shift 3', 'tiga'])) $shift = '3';
        elseif (in_array($text, ['reguler', 'shift reguler', 'r'])) $shift = 'reguler';

        if (!$shift) {
            return [
                'message'  => 'Pilih shift yang valid: 1, 2, 3, atau reguler.',
                'keyboard' => [
                    ['text' => 'Shift 1', 'callback_data' => 'wizard:confirm:shift_1'],
                    ['text' => 'Shift 2', 'callback_data' => 'wizard:confirm:shift_2'],
                    ['text' => 'Shift 3', 'callback_data' => 'wizard:confirm:shift_3'],
                    ['text' => 'Reguler', 'callback_data' => 'wizard:confirm:shift_reguler'],
                ],
            ];
        }

        return $this->applyShiftAndAdvance($chatId, $shift, $state);
    }

    /**
     * Simpan shift dan lanjut ke step jenis laporan.
     *
     * @param  string $chatId
     * @param  string $shift
     * @param  array  $state
     * @return array
     */
    protected function applyShiftAndAdvance(string $chatId, string $shift, array $state): array
    {
        $state['shift'] = $shift;
        $state['step']  = self::STEP_REPORT_TYPE;
        $this->saveState($chatId, $state);
        return $this->buildReportTypePrompt($state);
    }

    // =========================================================
    // STEP JENIS (corrective/preventive)
    // =========================================================

    /**
     * Bangun prompt untuk memilih jenis laporan.
     *
     * @param  array $state
     * @return array
     */
    protected function buildReportTypePrompt(array $state): array
    {
        return [
            'message' => "Jenis pekerjaan ini *Corrective* atau *Preventive*?",
            'keyboard' => [
                ['text' => 'Corrective',  'callback_data' => 'wizard:confirm:type_corrective'],
                ['text' => 'Preventive',  'callback_data' => 'wizard:confirm:type_preventive'],
                ['text' => 'Batalkan Laporan', 'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses input teks jenis laporan.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleReportTypeInput(string $chatId, string $text, array $state): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['corrective', 'correct', 'perbaikan'])) {
            return $this->applyReportTypeAndAdvance($chatId, 'corrective', $state);
        }
        if (in_array($text, ['preventive', 'prevent', 'pencegahan'])) {
            return $this->applyReportTypeAndAdvance($chatId, 'preventive', $state);
        }

        return [
            'message'  => 'Pilih jenis: *Corrective* atau *Preventive*?',
            'keyboard' => [
                ['text' => 'Corrective', 'callback_data' => 'wizard:confirm:type_corrective'],
                ['text' => 'Preventive', 'callback_data' => 'wizard:confirm:type_preventive'],
            ],
        ];
    }

    /**
     * Simpan jenis dan lanjut ke step status.
     *
     * @param  string $chatId
     * @param  string $type
     * @param  array  $state
     * @return array
     */
    protected function applyReportTypeAndAdvance(string $chatId, string $type, array $state): array
    {
        $state['report_type'] = $type;
        $state['step']        = self::STEP_STATUS;
        $this->saveState($chatId, $state);
        return $this->buildStatusPrompt($state);
    }

    // =========================================================
    // STEP STATUS (selesai/belum selesai)
    // =========================================================

    /**
     * Bangun prompt untuk memilih status pekerjaan.
     *
     * @param  array $state
     * @return array
     */
    protected function buildStatusPrompt(array $state): array
    {
        return [
            'message' => "Apakah pekerjaan ini sudah *selesai* atau *belum selesai*?",
            'keyboard' => [
                ['text' => 'Pekerjaan Selesai',      'callback_data' => 'wizard:confirm:status_selesai'],
                ['text' => 'Belum Selesai',          'callback_data' => 'wizard:confirm:status_belum_selesai'],
                ['text' => 'Batalkan Laporan',       'callback_data' => 'wizard:cancel_wizard'],
            ],
        ];
    }

    /**
     * Proses input teks status.
     *
     * @param  string $chatId
     * @param  string $text
     * @param  array  $state
     * @return array
     */
    protected function handleStatusInput(string $chatId, string $text, array $state): array
    {
        $text = strtolower(trim($text));

        if (in_array($text, ['selesai', 'selesai', 'done', 'ya'])) {
            return $this->applyStatusAndAdvance($chatId, 'selesai', $state);
        }
        if (in_array($text, ['belum selesai', 'belum', 'tidak', 'no'])) {
            return $this->applyStatusAndAdvance($chatId, 'belum_selesai', $state);
        }

        return [
            'message'  => 'Pilih: *Selesai* atau *Belum Selesai*?',
            'keyboard' => [
                ['text' => 'Pekerjaan Selesai',      'callback_data' => 'wizard:confirm:status_selesai'],
                ['text' => 'Belum Selesai',          'callback_data' => 'wizard:confirm:status_belum_selesai'],
            ],
        ];
    }

    /**
     * Simpan status dan lanjut ke step durasi.
     *
     * @param  string $chatId
     * @param  string $status
     * @param  array  $state
     * @return array
     */
    protected function applyStatusAndAdvance(string $chatId, string $status, array $state): array
    {
        $state['status'] = $status;
        return $this->advanceToWorkDuration($chatId, $state);
    }
}
