<?php

namespace App\Services\Telegram;

use App\Models\Asset;
use App\Services\Telegram\Traits\WizardCallbackHandlerTrait;
use App\Services\Telegram\Traits\WizardPhotoAddonTrait;
use App\Services\Telegram\Traits\WizardReportSaverTrait;
use App\Services\Telegram\Traits\WizardStepHandlerTrait;
use App\Services\Telegram\Traits\WizardUtilityTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReportWizardService
{
    use WizardStepHandlerTrait;
    use WizardCallbackHandlerTrait;
    use WizardReportSaverTrait;
    use WizardUtilityTrait;
    use WizardPhotoAddonTrait;

    const CACHE_PREFIX = 'report_wizard:';
    const CACHE_TTL = 7200;

    const STEP_INITIAL = 'initial';
    const STEP_EQUIPMENT_SEARCH = 'equipment_search';
    const STEP_EQUIPMENT_CLARIFY = 'equipment_clarify';
    const STEP_WORK_DURATION = 'work_duration';
    const STEP_ROOT_CAUSE = 'root_cause';
    const STEP_PHOTO_DOCUMENTATION = 'photo_documentation';
    const STEP_CONFIRMATION = 'confirmation';
    const STEP_DONE = 'done';

    const ROOT_CAUSE_MIN_LENGTH = 3;

    protected PhotoStorageService $photoStorage;

    public function __construct(PhotoStorageService $photoStorage)
    {
        $this->photoStorage = $photoStorage;
    }

    /**
     * Mulai sesi wizard baru untuk chat tertentu.
     * Hancurkan sesi sebelumnya jika ada, buat state awal,
     * lalu lanjut ke Step 1: pencarian equipment.
     *
     * @param  string      $chatId       Chat ID Telegram
     * @param  string      $text         Teks laporan awal dari teknisi
     * @param  string|null $photoFileId  File ID foto jika dikirim bersamaan
     * @return array       Respons
     */
    public function startWizard(string $chatId, string $text, ?string $photoFileId = null): array
    {
        $this->destroyWizard($chatId);
        $state = $this->createInitialState($chatId, $text);
        if ($photoFileId) {
            $state['initial_photo_file_id'] = $photoFileId;
        }
        $this->saveState($chatId, $state);
        return $this->processEquipmentSearch($chatId, $state);
    }

    /**
     * Proses input teks dari teknisi berdasarkan step wizard saat ini.
     *
     * @param  string $chatId Chat ID Telegram
     * @param  string $text   Input teks dari teknisi
     * @return array  Respons
     */
    public function handleTextInput(string $chatId, string $text): array
    {
        $state = $this->getState($chatId);
        if (!$state) {
            return $this->errorResponse('Tidak ada sesi aktif. Kirim laporan baru untuk memulai.');
        }

        switch ($state['step']) {
            case self::STEP_EQUIPMENT_CLARIFY:
                return $this->handleEquipmentRetype($chatId, $text, $state);
            case self::STEP_WORK_DURATION:
                return $this->handleDurationInput($chatId, $text, $state);
            case self::STEP_ROOT_CAUSE:
                return $this->handleRootCauseInput($chatId, $text, $state);
            case self::STEP_PHOTO_DOCUMENTATION:
                return $this->handlePhotoCommand($chatId, $text, $state, 'documentation');
            case self::STEP_CONFIRMATION:
                return $this->handleConfirmation($chatId, $text, $state);
            default:
                return $this->errorResponse('Ikuti instruksi bot ya.');
        }
    }

    /**
     * Proses input foto dari teknisi.
     * Foto yang masuk akan diproses oleh PhotoStorageService,
     * disimpan ke Storage, baru path-nya dimasukkan ke state wizard.
     *
     * @param  string $chatId Chat ID Telegram
     * @param  string $fileId File ID foto dari Telegram
     * @return array  Respons
     */
    public function handlePhotoInput(string $chatId, string $fileId): array
    {
        $state = $this->getState($chatId);
        if (!$state) {
            return $this->errorResponse('Tidak ada sesi aktif.');
        }

        if ($state['step'] === self::STEP_PHOTO_DOCUMENTATION) {
            // Proses foto lewat PhotoStorageService dulu, baru simpan path-nya
            $path = $this->photoStorage->store($fileId, $chatId);
            if ($path === null) {
                return [
                    'message'  => 'Gagal menyimpan foto. Coba kirim ulang.',
                    'keyboard' => [
                        ['text' => 'Selesai, Lanjutkan', 'callback_data' => 'wizard:confirm:photo_doc_done'],
                        ['text' => 'Coba Lagi',          'callback_data' => 'wizard:confirm:photo_doc_more'],
                    ],
                ];
            }

            return $this->addPhotoToStep($chatId, $path, $state, 'documentation');
        }

        return $this->errorResponse('Sekarang bukan saatnya upload foto.');
    }

    /**
     * Proses callback data dari inline keyboard Telegram.
     *
     * @param  string $chatId       Chat ID Telegram
     * @param  string $callbackData Data callback
     * @return array  Respons
     */
    public function handleCallback(string $chatId, string $callbackData): array
    {
        $state = $this->getState($chatId);
        if (!$state) {
            return $this->errorResponse('Sesi tidak ditemukan.');
        }
        return $this->routeCallback($chatId, $callbackData, $state);
    }

    // =========================================================
    // STEP 1 — PENCARIAN EQUIPMENT
    // =========================================================

    /**
     * Cari equipment berdasarkan teks laporan menggunakan pencocokan
     * tag_no atau kata kunci di kolom description.
     *
     * Alur:
     *   1. Cek apakah teks mengandung tag_no eksak (format A-xxxx atau xx-xx-xx)
     *   2. Jika tidak, cari kata kunci dari teks ke description Asset
     *   3. Jika ditemukan 1 -> kunci langsung
     *   4. Jika > 1 -> tampilkan daftar kandidat
     *   5. Jika 0 -> tanya apakah area work atau ketik ulang
     *
     * @param  string $chatId Chat ID Telegram
     * @param  array  $state  State wizard saat ini
     * @return array  Respons
     */
    protected function processEquipmentSearch(string $chatId, array $state): array
    {
        $text = $state['text'] ?? '';

        // Coba deteksi tag_no eksak di dalam teks
        $tagNo = $this->extractTagNo($text);
        if ($tagNo) {
            $asset = Asset::where('tag_no', $tagNo)->first();
            if ($asset) {
                return $this->lockEquipmentAndAdvance($chatId, $asset, $state);
            }
        }

        // Cari kata kunci dari teks
        $keywords = $this->extractKeywords($text);
        $assets   = $this->searchAssets($keywords);

        if ($assets->count() === 0) {
            // Tidak ditemukan — tanya kerja area atau ketik ulang
            $state['step'] = self::STEP_EQUIPMENT_CLARIFY;
            $state['retype_attempts'] = 0;
            $this->saveState($chatId, $state);

            return [
                'message'  => "Equipment tidak ditemukan dari laporan kamu.\n\n" .
                    "Pilih salah satu:\n" .
                    "1. *Ketik ulang* kode equipment\n" .
                    "2. *Kerja area* (tanpa equipment spesifik)",
                'keyboard' => [
                    ['text' => 'Ketik Ulang', 'callback_data' => 'equipment_candidate:retype'],
                    ['text' => 'Kerja Area',  'callback_data' => 'work_type:area'],
                ],
            ];
        }

        if ($assets->count() === 1) {
            // Satu kandidat — kunci langsung
            return $this->lockEquipmentAndAdvance($chatId, $assets->first(), $state);
        }

        // Banyak kandidat — tampilkan daftar
        $state['step'] = self::STEP_EQUIPMENT_SEARCH;
        $this->saveState($chatId, $state);

        $msg = "Ditemukan beberapa equipment:\n";
        $keyboard = [];
        foreach ($assets->take(10) as $asset) {
            $msg .= "\n- {$asset->tag_no} — {$asset->description}";
            $keyboard[] = [
                'text'          => $asset->tag_no,
                'callback_data' => "equipment_candidate:{$asset->id}",
            ];
        }

        if ($assets->count() > 10) {
            $msg .= "\n\n... dan " . ($assets->count() - 10) . " lainnya.";
        }

        $msg .= "\n\nPilih equipment atau ketik ulang:";
        $keyboard[] = ['text' => 'Ketik Ulang', 'callback_data' => 'equipment_candidate:retype'];

        return [
            'message'  => $msg,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * Handle ketika teknisi mengetik ulang kode equipment.
     * Cari berdasarkan input teks baru.
     * Jika gagal 3 kali, fallback ke kerja area.
     *
     * @param  string $chatId Chat ID Telegram
     * @param  string $text   Input teks baru dari teknisi
     * @param  array  $state  State wizard saat ini
     * @return array  Respons
     */
    protected function handleEquipmentRetype(string $chatId, string $text, array $state): array
    {
        $attempts = ($state['retype_attempts'] ?? 0) + 1;
        $state['retype_attempts'] = $attempts;

        if ($attempts > 3) {
            // Fallback ke kerja area setelah 3 kali gagal
            $state['is_area_work'] = true;
            $this->saveState($chatId, $state);
            return $this->advanceToWorkDuration($chatId, $state);
        }

        // Cari tag_no eksak
        $tagNo = $this->extractTagNo($text);
        if ($tagNo) {
            $asset = Asset::where('tag_no', $tagNo)->first();
            if ($asset) {
                return $this->lockEquipmentAndAdvance($chatId, $asset, $state);
            }
        }

        // Cari kata kunci
        $keywords = $this->extractKeywords($text);
        $assets   = $this->searchAssets($keywords);

        if ($assets->count() === 0) {
            $remaining = 3 - $attempts;
            return [
                'message'  => "Equipment \"{$text}\" tidak ditemukan.\n" .
                    "Sisa percobaan: {$remaining}x\n\n" .
                    "Ketik ulang kode equipment, atau pilih *Kerja Area*:",
                'keyboard' => [
                    ['text' => 'Kerja Area', 'callback_data' => 'work_type:area'],
                ],
            ];
        }

        if ($assets->count() === 1) {
            return $this->lockEquipmentAndAdvance($chatId, $assets->first(), $state);
        }

        // Tampilkan kandidat
        $msg = "Ditemukan beberapa:\n";
        $keyboard = [];
        foreach ($assets->take(10) as $asset) {
            $msg .= "\n- {$asset->tag_no} — {$asset->description}";
            $keyboard[] = [
                'text'          => $asset->tag_no,
                'callback_data' => "equipment_candidate:{$asset->id}",
            ];
        }

        $msg .= "\n\nPilih atau ketik ulang:";
        $keyboard[] = ['text' => 'Ketik Ulang', 'callback_data' => 'equipment_candidate:retype'];

        return [
            'message'  => $msg,
            'keyboard' => $keyboard,
        ];
    }

            /**
     * Cek apakah ada wizard aktif untuk chat tertentu.
     *
     * @param string $chatId
     * @return bool
     */
    public function hasActiveWizard(string $chatId): bool
    {
        return Cache::has(self::CACHE_PREFIX . $chatId);
    }

    // =========================================================
    // HELPER PENCARIAN
    // =========================================================

    /**
     * Ekstrak tag_no dari teks menggunakan pattern format A-xxxx atau xx-xx-xx.
     *
     * @param  string      $text
     * @return string|null
     */
    protected function extractTagNo(string $text): ?string
    {
        // Pattern: A-XXXX (alfa-semu-numerik)
        if (preg_match('/[A-Za-z]\-\d{3,4}/', $text, $m)) {
            return strtoupper($m[0]);
        }

        // Pattern: XX-XX-XX (format numerik)
        if (preg_match('/\d{2}-\d{2}-\d{2}/', $text, $m)) {
            return $m[0];
        }

        return null;
    }

    /**
     * Ekstrak kata kunci pencarian dari teks.
     * Hanya ambil kata dengan panjang >= 3 karakter, buang kata umum.
     *
     * @param  string $text
     * @return array  Array of keyword string
     */
    protected function extractKeywords(string $text): array
    {
        $stopWords = ['yang', 'dan', 'di', 'ke', 'dari', 'ini', 'itu', 'ada',
                      'tidak', 'sudah', 'belum', 'akan', 'dengan', 'untuk',
                      'pada', 'saya', 'kami', 'kita', 'oleh', 'atau', 'saat',
                      'juga', 'dapat', 'bisa', 'harus', 'setelah', 'sebelum'];

        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, fn($w) => strlen($w) >= 3 && !in_array($w, $stopWords));

        return array_values($words);
    }

    /**
     * Cari Asset berdasarkan kata kunci.
     * Cocokkan dengan tag_no, description, atau functional_location.
     *
     * @param  array $keywords Array of keyword string
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function searchAssets(array $keywords): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($keywords)) {
            return collect();
        }

        $query = Asset::query();

        foreach ($keywords as $keyword) {
            $like = '%' . $keyword . '%';
            $query->where(function ($q) use ($like) {
                $q->where('tag_no', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('functional_location', 'like', $like);
            });
        }

        return $query->limit(20)->get();
    }

    /**
     * Ambil state wizard dari cache.
     *
     * @param  string     $chatId
     * @return array|null
     */
    public function getState(string $chatId): ?array
    {
        $state = Cache::get(self::CACHE_PREFIX . $chatId);
        return $state ?: null;
    }

    /**
     * Simpan state wizard ke cache.
     *
     * @param string $chatId
     * @param array  $state
     * @return void
     */
    protected function saveState(string $chatId, array $state): void
    {
        Cache::put(self::CACHE_PREFIX . $chatId, $state, now()->addSeconds(self::CACHE_TTL));
    }

    /**
     * Hancurkan sesi wizard (hapus state dari cache).
     *
     * @param string $chatId
     * @return void
     */
    protected function destroyWizard(string $chatId): void
    {
        Cache::forget(self::CACHE_PREFIX . $chatId);
    }
}
