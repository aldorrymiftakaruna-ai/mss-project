<?php

namespace App\Services\Telegram;

use App\Models\Asset;
use App\Services\AiService;
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
    use \App\Services\Telegram\Traits\WizardNewStepsTrait;

    const CACHE_PREFIX = 'report_wizard:';
    const CACHE_TTL = 7200;

        const STEP_INITIAL = 'initial';
    const STEP_EQUIPMENT_SEARCH = 'equipment_search';
    const STEP_EQUIPMENT_CLARIFY = 'equipment_clarify';
    const STEP_SHIFT = 'shift';
    const STEP_REPORT_TYPE = 'report_type';
    const STEP_STATUS = 'status';
    const STEP_WORK_DURATION = 'work_duration';
    const STEP_ROOT_CAUSE = 'root_cause';
    const STEP_PHOTO_DOCUMENTATION = 'photo_documentation';
    const STEP_CATATAN = 'catatan';
    const STEP_DOWNTIME = 'downtime';
        const STEP_OVERTIME = 'overtime';
    const STEP_CONFIRMATION = 'confirmation';
    const STEP_DONE = 'done';

    const ROOT_CAUSE_MIN_LENGTH = 3;

        protected PhotoStorageService $photoStorage;

    protected AiService $aiService;

    public function __construct(PhotoStorageService $photoStorage, AiService $aiService)
    {
        $this->photoStorage = $photoStorage;
        $this->aiService    = $aiService;
    }

        /**
     * Mulai sesi wizard baru untuk chat tertentu.
     * Hancurkan sesi sebelumnya jika ada, buat state awal,
     * lalu jalankan AI analysis untuk mendeteksi asset, durasi, dan root cause.
     * Kemudian lanjut ke Step 1: pencarian equipment.
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

        // Analisis teks laporan via AI (Groq/LLM) untuk deteksi awal asset,
        // durasi, dan root cause. Jika AI tidak tersedia, fallback ke keyword matching.
        $analysis = $this->aiService->analyzeReportText($text);
        $state['ai_analysis'] = $analysis;

        // Isi state dengan hasil AI agar step-step wizard berikutnya auto-terisi
        if (!empty($analysis['detected_asset_id'])) {
            $asset = Asset::find($analysis['detected_asset_id']);
            if ($asset) {
                $state['equipment_id']         = $asset->id;
                $state['equipment_tag']        = $asset->tag_no;
                $state['equipment_description'] = $asset->description;
            }
        }

        if (!empty($analysis['report_type']) && in_array($analysis['report_type'], ['corrective', 'preventive'])) {
            $state['report_type'] = $analysis['report_type'];
        }

        if (!empty($analysis['parsed_duration_minutes'])) {
            $state['work_duration_minutes'] = (int) $analysis['parsed_duration_minutes'];
        }

        if (!empty($analysis['parsed_root_cause'])) {
            $state['root_cause'] = $analysis['parsed_root_cause'];
        }

        if ($photoFileId) {
            $state['initial_photo_file_id'] = $photoFileId;
        }

        $this->saveState($chatId, $state);

        Log::info('Wizard: AI analysis completed', [
            'chat_id'    => $chatId,
            'confidence' => $analysis['confidence'] ?? 0,
            'asset_id'   => $state['equipment_id'],
            'duration'   => $state['work_duration_minutes'],
            'root_cause' => $state['root_cause'],
        ]);

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
            case self::STEP_SHIFT:
                return $this->handleShiftInput($chatId, $text, $state);
            case self::STEP_REPORT_TYPE:
                return $this->handleReportTypeInput($chatId, $text, $state);
            case self::STEP_STATUS:
                return $this->handleStatusInput($chatId, $text, $state);
            case self::STEP_WORK_DURATION:
                return $this->handleDurationInput($chatId, $text, $state);
            case self::STEP_ROOT_CAUSE:
                return $this->handleRootCauseInput($chatId, $text, $state);
                        case self::STEP_PHOTO_DOCUMENTATION:
                return $this->handlePhotoCommand($chatId, $text, $state, 'documentation');
                        case self::STEP_CATATAN:
                return $this->handleCatatanInput($chatId, $text, $state);
            case self::STEP_DOWNTIME:
                return $this->handleDowntimeInput($chatId, $text, $state);
            case self::STEP_OVERTIME:
                return $this->handleOvertimeInput($chatId, $text, $state);
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
     *   5. Jika 0 -> minta ketik ulang kode equipment
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
            // Tidak ditemukan — minta ketik ulang kode equipment
            $state['step'] = self::STEP_EQUIPMENT_CLARIFY;
            $state['retype_attempts'] = 0;
            $this->saveState($chatId, $state);

            return [
                'message'  => "Equipment tidak ditemukan dari laporan kamu.\n\n" .
                    "Ketik ulang kode equipment:",
                'keyboard' => [
                    ['text' => 'Ketik Ulang', 'callback_data' => 'equipment_candidate:retype'],
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
     * Jika gagal 3 kali, wizard dibatalkan.
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
            $this->destroyWizard($chatId);
            return [
                'message'  => 'Equipment tidak ditemukan setelah 3 kali percobaan. Laporan dibatalkan. Ketik pesan baru untuk memulai laporan dari awal.',
                'keyboard' => [],
            ];
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
                    "Ketik ulang kode equipment:",
                'keyboard' => [],
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

        $words = preg_split('/[\s,\.\!\?]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, function ($w) use ($stopWords) {
            // Kata pendek (< 3 karakter) tetap diambil jika kombinasi huruf+angka (kode asset)
            if (strlen($w) < 3) {
                return preg_match('/[a-z]/i', $w) && preg_match('/\d/', $w);
            }
            return !in_array($w, $stopWords);
        });

        return array_values($words);
    }

        /**
         * Pisahkan keyword menjadi kandidat tag asset (mengandung huruf DAN
         * angka sekaligus) dan kata umum (tanpa kombinasi huruf+angka).
         * Kandidat tag diprioritaskan untuk pencarian asset karena pola
         * tag_no di sistem ini selalu campuran huruf+angka (verifikasi
         * 03/07/2026, contoh: P01, RS01, LB04, SC03A, 6842FN02).
         *
         * @param  array $keywords Array of keyword string
         * @return array ['tag_candidates' => [...], 'general_words' => [...]]
         */
        protected function separateTagCandidates(array $keywords): array
        {
            $tagCandidates = [];
            $generalWords  = [];

            foreach ($keywords as $word) {
                $hasLetter = preg_match('/[a-z]/i', $word);
                $hasDigit  = preg_match('/\d/', $word);

                if ($hasLetter && $hasDigit) {
                    $tagCandidates[] = $word;
                } else {
                    $generalWords[] = $word;
                }
            }

            return [
                'tag_candidates' => $tagCandidates,
                'general_words'  => $generalWords,
            ];
        }

        /**
         * Cari Asset berdasarkan kata kunci.
         * Jika ada tag_candidates (kata mengandung huruf+angka), HANYA
         * pakai itu untuk pencarian (AND). Jika tidak ada, pakai semua
         * general_words dengan OR di dalam satu grouped where.
         *
         * @param  array $keywords Array of keyword string
         * @return \Illuminate\Database\Eloquent\Collection
         */
        protected function searchAssets(array $keywords): \Illuminate\Database\Eloquent\Collection
        {
            if (empty($keywords)) {
                return new \Illuminate\Database\Eloquent\Collection();
            }

            $separated = $this->separateTagCandidates($keywords);
            $tagCandidates = $separated['tag_candidates'];
            $generalWords  = $separated['general_words'];

            $query = Asset::query();

            if (!empty($tagCandidates)) {
                // Prioritas: cari berdasarkan tag_candidates saja (AND)
                foreach ($tagCandidates as $keyword) {
                    $like = '%' . $keyword . '%';
                    $query->where(function ($q) use ($like) {
                        $q->where('tag_no', 'like', $like)
                          ->orWhere('description', 'like', $like);
                    });
                }
            } elseif (!empty($generalWords)) {
                // Fallback: cari berdasarkan general_words dengan OR
                // Semua kondisi dibungkus dalam satu where() agar tidak
                // merusak scoping query dari luar (misal ->where('company_id', ...))
                $query->where(function ($q) use ($generalWords) {
                    foreach ($generalWords as $keyword) {
                        $like = '%' . $keyword . '%';
                        $q->orWhere(function ($sub) use ($like) {
                            $sub->where('tag_no', 'like', $like)
                                ->orWhere('description', 'like', $like);
                        });
                    }
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
    public function destroyWizard(string $chatId): void
    {
        Cache::forget(self::CACHE_PREFIX . $chatId);
    }
}

