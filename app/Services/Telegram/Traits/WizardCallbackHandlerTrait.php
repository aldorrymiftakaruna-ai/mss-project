<?php

namespace App\Services\Telegram\Traits;

use App\Models\Asset;
use Illuminate\Support\Facades\Log;

trait WizardCallbackHandlerTrait
{
    protected function routeCallback(string $chatId, string $callbackData, array $state): array
    {
        if (str_starts_with($callbackData, 'wizard:confirm:')) {
            return $this->handleConfirmationCallback($chatId, $callbackData, $state);
        }
        if ($callbackData === 'wizard:cancel_wizard') {
            $this->destroyWizard($chatId);
            return ['message' => 'Laporan dibatalkan.', 'keyboard' => []];
        }
        if (str_starts_with($callbackData, 'equipment_candidate:')) {
            return $this->handleEquipmentCandidateCallback($chatId, $callbackData, $state);
        }
        return $this->errorResponse('Callback tidak dikenali.');
    }

    protected function handleConfirmationCallback(string $chatId, string $callbackData, array $state): array
    {
        $action = str_replace('wizard:confirm:', '', $callbackData);

        switch (true) {
            // Shift
            case str_starts_with($action, 'shift_'):
                $shift = str_replace('shift_', '', $action);
                return $this->applyShiftAndAdvance($chatId, $shift, $state);

            // Jenis laporan
            case $action === 'type_corrective':
                return $this->applyReportTypeAndAdvance($chatId, 'corrective', $state);
            case $action === 'type_preventive':
                return $this->applyReportTypeAndAdvance($chatId, 'preventive', $state);

            // Status
            case $action === 'status_selesai':
                return $this->applyStatusAndAdvance($chatId, 'selesai', $state);
            case $action === 'status_belum_selesai':
                return $this->applyStatusAndAdvance($chatId, 'belum_selesai', $state);

            // Durasi
            case $action === 'duration_ok':
                $state['step'] = self::STEP_ROOT_CAUSE;
                $this->saveState($chatId, $state);
                return $this->buildRootCausePrompt($state);
            case $action === 'duration_change':
                $state['work_duration_minutes'] = null;
                $this->saveState($chatId, $state);
                return ['message' => 'Ketik durasi pekerjaan:', 'keyboard' => []];

            // Root cause
            case $action === 'rootcause_ok':
                $state['step'] = self::STEP_PHOTO_DOCUMENTATION;
                $this->saveState($chatId, $state);
                return $this->buildPhotoDocumentationPrompt($state);
            case $action === 'rootcause_change':
                $state['root_cause'] = null;
                $this->saveState($chatId, $state);
                return ['message' => 'Ketik root cause:', 'keyboard' => []];

                        // Foto
            case $action === 'photo_doc_more':
                return ['message' => 'Kirim foto tambahan:', 'keyboard' => []];
            case $action === 'photo_doc_done':
            case $action === 'photo_doc_skip':
                return $this->advanceFromPhotoStep($chatId, $state, 'documentation');

            // Catatan / Feedback
            case $action === 'catatan_skip':
                return $this->advanceFromCatatan($chatId, $state);

            // Simpan / Batal
            case $action === 'save_report':
                return $this->saveReport($chatId, $state);
            case $action === 'cancel_report':
                $this->destroyWizard($chatId);
                return ['message' => 'Laporan dibatalkan.', 'keyboard' => []];

            default:
                return $this->errorResponse('Callback tidak dikenal: ' . $action);
        }
    }

    protected function handleEquipmentCandidateCallback(string $chatId, string $callbackData, array $state): array
    {
        $parts = explode(':', $callbackData);
        $id = $parts[1] ?? null;

        if ($id === 'retype') {
            $state['retype_attempts'] = ($state['retype_attempts'] ?? 0);
            $this->saveState($chatId, $state);
            return ['message' => 'Ketik ulang kode equipment:', 'keyboard' => []];
        }

        $asset = Asset::find((int) $id);
        if (!$asset) return $this->errorResponse('Equipment tidak ditemukan.');

        return $this->lockEquipmentAndAdvance($chatId, $asset, $state);
    }

    /**
     * Setelah equipment dipilih, lanjut ke step shift.
     */
    protected function lockEquipmentAndAdvance(string $chatId, Asset $asset, array $state): array
    {
        $state['equipment_id'] = $asset->id;
        $state['equipment_tag'] = $asset->tag_no;
        $state['equipment_description'] = $asset->description;

        return $this->advanceAfterEquipment($chatId, $state);
    }
}
