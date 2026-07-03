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
        switch ($action) {
            case 'duration_ok':
                $state['step'] = self::STEP_ROOT_CAUSE; $this->saveState($chatId, $state);
                return $this->buildRootCausePrompt($state);
            case 'duration_change':
                $state['work_duration_minutes'] = null; $this->saveState($chatId, $state);
                return ['message' => 'Ketik durasi pekerjaan:', 'keyboard' => []];
            case 'rootcause_ok':
                $state['step'] = self::STEP_PHOTO_DOCUMENTATION; $this->saveState($chatId, $state);
                return $this->buildPhotoDocumentationPrompt($state);
            case 'rootcause_change':
                $state['root_cause'] = null; $this->saveState($chatId, $state);
                return ['message' => 'Ketik root cause:', 'keyboard' => []];
            case 'photo_doc_more':
                return ['message' => 'Kirim foto tambahan:', 'keyboard' => []];
            case 'photo_doc_done':
            case 'photo_doc_skip':
                return $this->advanceFromPhotoStep($chatId, $state, 'documentation');
            case 'save_report':
                return $this->saveReport($chatId, $state);
            case 'cancel_report':
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
    
    
    protected function lockEquipmentAndAdvance(string $chatId, Asset $asset, array $state): array
    {
        $state['equipment_id'] = $asset->id;
        $state['equipment_tag'] = $asset->tag_no;
        $state['equipment_description'] = $asset->description;
        return $this->advanceToWorkDuration($chatId, $state);
    }
}
