<?php

namespace App\Console\Commands\Traits;

use App\Models\BotRegistration;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trait TelegramMessageHandlerTrait
 *
 * Digunakan oleh PollTelegramUpdates untuk routing dan pemrosesan semua
 * pesan masuk dari Telegram (teks, foto, dan navigasi internal wizard).
 *
 * Trait ini bergantung pada TelegramSenderTrait (untuk mengirim balasan)
 * dan properti $reportWizard serta $photoStorage yang didefinisikan
 * di PollTelegramUpdates.
 *
 * CATATAN: Teknisi direpresentasikan oleh model Employee (bukan Technician).
 * Pencarian berdasarkan kolom telegram_id (bigint) di tabel employees.
 *
 * Method yang tersedia:
 *   - processUpdate()            : Entry point routing pesan teks dan foto
 *   - handlePhotoMessage()       : Routing pesan foto (wizard / RPT code / wizard baru)
 *   - handleWizardText()         : Teruskan teks ke wizard aktif
 *   - handleWizardCallback()     : Teruskan callback ke wizard aktif
 *   - dispatchWizardResponse()   : Kirim balasan wizard ke Telegram
 *   - handleStart()              : Proses perintah /start dan pendaftaran awal
 *   - handleNikRegistration()    : Simpan NIK, cari Employee, simpan telegram_id
 *   - handleReport()             : Mulai wizard laporan baru
 */
trait TelegramMessageHandlerTrait
{
    /**
     * Proses satu update pesan dari Telegram (routing utama).
     *
     * Urutan routing:
     *   1. /start command
     *   2. NIK registration (pola "NIK 123456")
     *   3. Validasi employee terdaftar & aktif (berdasarkan telegram_id)
     *   4. Foto -> handlePhotoMessage
     *   5. Teks: wizard aktif -> handleWizardText, selain itu -> handleReport
     *
     * @param array $update Update mentah dari Telegram getUpdates
     * @return void
     */
    private function processUpdate(array $update): void
    {
        $message    = $update['message'];
        $chatId     = $message['chat']['id'];
        $text       = $message['text'] ?? '';
        $from       = $message['from'] ?? [];
        $telegramId = (string) ($from['id'] ?? '');
        $username   = $from['username'] ?? null;
        $firstName  = $from['first_name'] ?? '';
        $hasPhoto   = !empty($message['photo']);
        $caption    = $message['caption'] ?? '';

        $this->line("Pesan dari {$firstName} (@{$username}): " . Str::limit($text ?: $caption ?: '[foto]', 80));

        // Handle /start
        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $telegramId, $username, $firstName);
            return;
        }

        // Handle NIK registration
        if (preg_match('/^NIK\s+(\S+)$/i', $text, $matches)) {
            $this->handleNikRegistration($chatId, $telegramId, $matches[1]);
            return;
        }

        // Cek employee terdaftar & aktif via telegram_id
        $employee = Employee::where('telegram_id', $telegramId)
            ->where('is_active', true)
            ->first();

        if (!$employee) {
            $this->sendMessage($chatId, 'Maaf, akun kamu belum terdaftar atau belum disetujui. Silakan hubungi admin.');
            return;
        }

        // Routing foto
        if ($hasPhoto) {
            $this->handlePhotoMessage($chatId, $message, $employee, $caption);
            return;
        }

        // Routing teks: wizard aktif atau mulai baru
        if ($this->reportWizard->hasActiveWizard((string) $chatId)) {
            $this->handleWizardText($chatId, $text);
        } else {
            $this->handleReport($chatId, $employee, $text);
        }
    }

    /**
     * Handle pesan foto dari teknisi.
     *
     * Tiga kemungkinan alur:
     *   A) Wizard aktif di step foto -> teruskan ke wizard (download + simpan dulu)
     *   B) Caption mengandung kode RPT-... -> tambah foto ke laporan lama
     *   C) Tidak keduanya -> mulai wizard baru, foto sebagai foto awal Step 1
     *
     * @param int|string $chatId  ID chat pengirim
     * @param array      $message Pesan lengkap dari Telegram
     * @param Employee   $employee Objek employee pengirim
     * @param string     $caption Caption foto (bisa kosong)
     * @return void
     */
    private function handlePhotoMessage(
        int|string $chatId,
        array $message,
        Employee $employee,
        string $caption
    ): void {
        $this->sendChatAction($chatId);

        // Ambil file_id resolusi tertinggi (foto Telegram dikirim multi-resolusi,
        // array terakhir adalah yang terbesar)
        $photos = $message['photo'] ?? [];
        if (empty($photos)) {
            $this->sendMessage($chatId, "Foto tidak bisa dibaca. Coba kirim ulang.");
            return;
        }
        $bestPhoto = end($photos);
        $fileId    = $bestPhoto['file_id'] ?? null;

        if (!$fileId) {
            $this->sendMessage($chatId, "File ID foto tidak ditemukan. Coba kirim ulang.");
            return;
        }

        // A) Wizard aktif -- teruskan ke wizard (PhotoStorageService akan dipanggil
        // oleh wizard sendiri di handlePhotoInput)
        if ($this->reportWizard->hasActiveWizard((string) $chatId)) {
            $response = $this->reportWizard->handlePhotoInput((string) $chatId, $fileId);
            $this->dispatchWizardResponse($chatId, $response);
            return;
        }

        // B) Caption mengandung kode RPT-... -> tambah foto ke laporan lama
        $reportCode = $this->reportWizard->extractReportCode($caption);
        if ($reportCode) {
            $response = $this->reportWizard->addPhotoToReport($reportCode, $fileId);

            if (!empty($response['error'])) {
                $this->sendMessage($chatId, $response['message'] ?? 'Gagal menambahkan foto.');
            } else {
                $this->sendMessage($chatId, $response['message'] ?? 'Foto ditambahkan ke laporan.');
            }
            return;
        }

        // C) Tidak ada wizard aktif & tidak ada RPT code -> mulai wizard baru
        $response = $this->reportWizard->startWizard(
            chatId:      (string) $chatId,
            text:        $caption ?: 'Laporan dengan foto',
            photoFileId: $fileId
        );
        $this->dispatchWizardResponse($chatId, $response);
    }

    /**
     * Teruskan teks ke ReportWizardService saat wizard sedang aktif.
     *
     * @param int|string $chatId ID chat
     * @param string     $text   Teks yang dikirim teknisi
     * @return void
     */
    private function handleWizardText(int|string $chatId, string $text): void
    {
        $this->sendChatAction($chatId);
        $response = $this->reportWizard->handleTextInput((string) $chatId, $text);
        $this->dispatchWizardResponse($chatId, $response);
    }

    /**
     * Teruskan callback ke ReportWizardService saat wizard sedang aktif.
     *
     * Dipanggil dari processCallbackQuery. Jika respons mengandung keyboard,
     * pesan diedit in-place; jika tidak, hanya teks yang diperbarui.
     * Jika laporan berhasil disimpan, kirim pesan terpisah dengan kode laporan.
     *
     * @param int|string $chatId    ID chat
     * @param int        $messageId ID pesan yang memicu callback
     * @param string     $data      Data callback dari tombol inline keyboard
     * @return void
     */
    private function handleWizardCallback(int|string $chatId, int $messageId, string $data): void
    {
        $response = $this->reportWizard->handleCallback((string) $chatId, $data);

        if (!empty($response['message'])) {
            if (!empty($response['keyboard'])) {
                $this->editMessageText($chatId, $messageId, $response['message'], $response['keyboard']);
            } else {
                $this->editMessageTextSimple($chatId, $messageId, $response['message']);
            }
        }

        // Jika laporan berhasil disimpan, kirim pesan terpisah dengan kode laporan
        if (!empty($response['saved']) && !empty($response['report_code'])) {
            $this->sendMessage($chatId, $response['message'] ?? "Laporan tersimpan.");
        }
    }

    /**
     * Dispatch respons dari wizard ke Telegram.
     *
     * Pesan wizard selalu dikirim sebagai pesan baru (bukan edit) karena
     * tiap step membuka konteks baru.
     *
     * @param int|string $chatId   ID chat tujuan
     * @param array      $response Respons dari ReportWizardService
     * @return void
     */
    private function dispatchWizardResponse(int|string $chatId, array $response): void
    {
        $message  = $response['message'] ?? '';
        $keyboard = $response['keyboard'] ?? [];

        if (empty($message)) {
            return;
        }

        if (!empty($keyboard)) {
            $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
        } else {
            $this->sendMessage($chatId, $message);
        }
    }

    /**
     * Proses perintah /start dan pendaftaran employee baru.
     *
     * Alur:
     *   - Jika sudah terdaftar & aktif -> sambut
     *   - Jika sudah terdaftar & tidak aktif -> beri tahu status
     *   - Jika ada pendaftaran pending -> beri tahu masih diproses
     *   - Jika belum ada -> buat BotRegistration pending dan minta NIK
     *
     * @param int|string  $chatId     ID chat
     * @param string      $telegramId Telegram user ID
     * @param string|null $username   Username Telegram (bisa null)
     * @param string      $firstName  Nama depan pengguna
     * @return void
     */
    private function handleStart(
        int|string $chatId,
        string $telegramId,
        ?string $username,
        string $firstName
    ): void {
        // Cari di Employee berdasarkan telegram_id
        $employee = Employee::where('telegram_id', $telegramId)->first();

        if ($employee) {
            if ($employee->is_active) {
                $this->sendMessage($chatId, "Halo $firstName! Akun kamu sudah aktif. Silakan kirim laporan harian.");
            } else {
                $this->sendMessage($chatId, "Akun kamu masih belum aktif. Silakan hubungi admin.");
            }
            return;
        }

        $pending = BotRegistration::where('telegram_id', $telegramId)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            $this->sendMessage($chatId, "Pendaftaran kamu masih diproses. Silakan tunggu konfirmasi dari admin.");
            return;
        }

        BotRegistration::create([
            'telegram_id'       => $telegramId,
            'telegram_username' => $username,
            'name'              => $firstName,
            'status'            => 'pending',
        ]);

        $this->sendMessage(
            $chatId,
            "Halo $firstName! Untuk mendaftar sebagai teknisi, silakan kirim NIK kamu.\n\nContoh: NIK 123456"
        );
    }

    /**
     * Proses NIK yang dikirim teknisi untuk registrasi.
     *
     * Alur:
     *   1. Cari BotRegistration pending untuk chat ini
     *   2. Cari Employee berdasarkan NIK (dicocokkan dengan nama di
     *      BotRegistration yang sudah disimpan dari /start)
     *   3. Jika Employee ditemukan, simpan telegram_id ke employee tsb
     *   4. Jika tidak ditemukan, beri tahu admin perlu intervensi manual
     *
     * @param int|string $chatId     ID chat
     * @param string     $telegramId Telegram user ID
     * @param string     $nik        NIK yang dikirim teknisi
     * @return void
     */
    private function handleNikRegistration(int|string $chatId, string $telegramId, string $nik): void
    {
        $registration = BotRegistration::where('telegram_id', $telegramId)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$registration) {
            $this->sendMessage($chatId, "Tidak ada pendaftaran yang menunggu untuk NIK. Silakan kirim /start dulu.");
            return;
        }

        // Simpan NIK ke registration
        $registration->update(['nik' => $nik]);

        // Cari Employee berdasarkan nama (dari BotRegistration)
        // Catatan: NIK belum ada di tabel employees; pendekatan terbaik
        // adalah mencocokkan nama employee dengan nama dari registrasi.
        $employee = Employee::where('name', $registration->name)->first();

        if ($employee) {
            // Simpan telegram_id ke Employee
            $employee->update([
                'telegram_id'       => $telegramId,
                'telegram_username' => $registration->telegram_username,
            ]);

            $this->sendMessage(
                $chatId,
                "Terima kasih! Akun kamu ($nik) sudah terdaftar dan siap digunakan. Silakan kirim laporan."
            );

            Log::info("Registrasi: Employee #{$employee->id} {$employee->name} terhubung dengan Telegram ID {$telegramId}");
        } else {
            // Employee tidak ditemukan, beri tahu
            $this->sendMessage(
                $chatId,
                "Terima kasih! NIK kamu ($nik) sudah tercatat. Admin akan memproses pendaftaran kamu segera."
            );
        }
    }

    /**
     * Mulai wizard laporan baru dari teks yang dikirim employee.
     *
     * Laporan hanya disimpan ke DB setelah employee mengonfirmasi di Step 8.
     *
     * @param int|string $chatId   ID chat
     * @param Employee   $employee Objek employee pengirim
     * @param string     $text     Teks laporan awal
     * @return void
     */
    private function handleReport(int|string $chatId, Employee $employee, string $text): void
    {
        $this->sendChatAction($chatId);
        $response = $this->reportWizard->startWizard((string) $chatId, $text);
        $this->dispatchWizardResponse($chatId, $response);
    }
}
