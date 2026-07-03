<?php

namespace App\Http\Controllers;

use App\Models\BotRegistration;
use App\Models\BotUnknownAsset;
use App\Models\Employee;
use App\Models\MaintenanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class BotController extends Controller
{
    /**
     * Tampilkan panel bot Telegram.
     *
     * Mengirim data untuk 6 kartu status dan 4 tab:
     * - Status & Koneksi
     * - Pendaftaran
     * - Teknisi Aktif Bot
     * - Unknown Assets
     *
     * Semua query menggunakan Employee (bukan Technician)
     * dan MaintenanceReport (bukan Report).
     */
    public function index()
    {
        $botToken = config('telegram.bot_token');
        $botStatus = !empty($botToken) ? 'terkonfigurasi' : 'belum_konfigurasi';

        $teknisiAktif = Employee::where('is_active', true)
            ->whereNotNull('telegram_id')
            ->count();

        $terhubungBot = Employee::whereNotNull('telegram_id')->count();

        $laporanViaBot = MaintenanceReport::whereNotNull('submitted_at')->count();

        $laporanHariIni = MaintenanceReport::whereNotNull('submitted_at')
            ->whereDate('submitted_at', today())
            ->count();

        $unknownAssetsTotal = BotUnknownAsset::count();

        $pendaftaran = BotRegistration::with('processedBy')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $teknisiList = Employee::whereNotNull('telegram_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $unknownAssets = BotUnknownAsset::with('maintenanceReport')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'bot_status'        => $botStatus,
            'teknisi_aktif'     => $teknisiAktif,
            'terhubung_bot'     => $terhubungBot,
            'laporan_via_bot'   => $laporanViaBot,
            'laporan_hari_ini'  => $laporanHariIni,
            'unknown_assets'    => $unknownAssetsTotal,
        ];

        return view('bot.index', compact(
            'stats',
            'pendaftaran',
            'teknisiList',
            'unknownAssets'
        ));
    }

    /**
     * Simpan pengaturan bot dari form panel.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'token'         => 'nullable|string',
            'status'        => 'required|in:active,inactive',
            'auto_approve'  => 'boolean',
            'max_item'      => 'integer|min:1|max:20',
            'notif_channel' => 'nullable|string',
        ]);

        return back()->with('success', 'Pengaturan bot berhasil disimpan.');
    }

    /**
     * Test koneksi ke Telegram API menggunakan bot token yang terkonfigurasi.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection()
    {
        try {
            $token = config('telegram.bot_token');

            if (empty($token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token bot belum dikonfigurasi. Isi TELEGRAM_BOT_TOKEN di file .env',
                ]);
            }

            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");

            if ($response->successful()) {
                $botInfo = $response->json()['result'] ?? [];
                return response()->json([
                    'success' => true,
                    'message' => "Koneksi berhasil! @{$botInfo['username']} ({$botInfo['first_name']}) terhubung.",
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . ($response->json()['description'] ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Set webhook Telegram ke URL aplikasi.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setWebhook()
    {
        try {
            $token = config('telegram.bot_token');

            if (empty($token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token bot belum dikonfigurasi.',
                ]);
            }

            $url      = route('telegram.webhook');
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $url,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook berhasil disetel ke: ' . $url,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . ($response->json()['description'] ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Hapus webhook Telegram.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteWebhook()
    {
        try {
            $token = config('telegram.bot_token');

            if (empty($token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token bot belum dikonfigurasi.',
                ]);
            }

            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/deleteWebhook");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook berhasil dihapus.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . ($response->json()['description'] ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Setujui pendaftaran teknisi bot.
     * Membuat employee baru dengan data dari registrasi.
     *
     * @param BotRegistration $registration
     * @return \Illuminate\Http\RedirectResponse
     */
        public function approveRegistration(BotRegistration $registration)
    {
        if (empty($registration->nik)) {
            return back()->with('error', 'NIK belum diisi oleh teknisi. Tidak bisa disetujui sebelum NIK tersedia.');
        }

        $existingEmployee = Employee::where('telegram_id', $registration->telegram_id)->first();
        if ($existingEmployee) {
            return back()->with('error', 'Teknisi dengan telegram_id ini sudah terdaftar sebagai employee.');
        }

        $registration->update([
            'status'       => 'approved',
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        Employee::create([
            'telegram_id'       => $registration->telegram_id,
            'telegram_username' => $registration->telegram_username,
            'name'              => $registration->name,
            'nik'               => $registration->nik,
            'is_active'         => true,
        ]);

        return back()->with('success', 'Pendaftaran ' . $registration->name . ' disetujui.');
    }

    /**
     * Tolak pendaftaran teknisi bot.
     *
     * @param BotRegistration $registration
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectRegistration(BotRegistration $registration)
    {
        $registration->update([
            'status'       => 'rejected',
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Pendaftaran ' . $registration->name . ' ditolak.');
    }

    /**
     * Start polling — menampilkan info untuk menjalankan manual via terminal.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startPolling()
    {
        return back()->with('info', 'Jalankan polling manual melalui terminal: <code>php artisan telegram:poll</code>');
    }

    /**
     * Stop polling — mengirim sinyal berhenti via file.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stopPolling()
    {
        $lockFile = storage_path('app/telegram_poll.lock');
        $stopFile = storage_path('app/telegram_poll.stop');

        try {
            file_put_contents($stopFile, time());
            return back()->with('success', 'Perintah berhenti telah dikirim. Polling akan berhenti dalam beberapa detik.');
        } catch (\Exception $e) {
            @unlink($lockFile);
            @unlink($stopFile);
            return back()->with('warning', 'Polling dihentikan.');
        }
    }

    /**
     * Cek status polling (apakah masih berjalan).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pollingStatus()
    {
        $lockFile = storage_path('app/telegram_poll.lock');
        $stopFile = storage_path('app/telegram_poll.stop');

        $running = false;
        if (file_exists($lockFile)) {
            $lockTime = (int) file_get_contents($lockFile);
            if (time() - $lockTime < 150) {
                $running = true;
            } else {
                @unlink($lockFile);
            }
        }

        if (file_exists($stopFile)) {
            $stopTime = (int) file_get_contents($stopFile);
            if (time() - $stopTime > 10) {
                @unlink($stopFile);
                @unlink($lockFile);
                $running = false;
            }
        }

        $logContent = '';
        $logFile    = storage_path('logs/telegram-poll.log');
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $lines      = explode("\n", $logContent);
            $logContent = implode("\n", array_slice($lines, -20));
        }

        return response()->json([
            'running'  => $running,
            'last_log' => $logContent ?: '(Belum ada log)',
        ]);
    }
}
