<?php

namespace App\Http\Controllers;

use App\Models\BotRegistration;
use App\Models\MaintenanceReport;
use App\Models\Employee;
use App\Models\BotUnknownAsset;
use App\Models\Asset;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function handle(Request $request)
    {
        try {
            $update = $request->all();

            if (isset($update['message'])) {
                $message    = $update['message'];
                $chatId     = $message['chat']['id'];
                $text       = $message['text'] ?? '';
                $from       = $message['from'] ?? [];
                $telegramId = (string) ($from['id'] ?? '');
                $username   = $from['username'] ?? null;
                $firstName  = $from['first_name'] ?? '';

                if (str_starts_with($text, '/start')) {
                    return $this->handleStart($chatId, $telegramId, $username, $firstName);
                }

                // Cek apakah user sedang dalam proses registrasi
                $pendingReg = BotRegistration::where('telegram_id', $telegramId)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                if ($pendingReg && $pendingReg->step === 'waiting_name') {
                    return $this->handleNameInput($chatId, $pendingReg, $text);
                }

                if ($pendingReg && $pendingReg->step === 'waiting_jabatan') {
                    return $this->handleJabatanInput($chatId, $pendingReg, $text);
                }

                $employee = Employee::where('telegram_id', $telegramId)
                    ->where('is_active', true)
                    ->first();

                if (!$employee) {
                    return $this->sendMessage($chatId, 'Harap menunggu persetujuan admin sebelum melakukan reporting.');
                }

                return $this->handleReport($chatId, $employee, $text);
            }

            return response('OK');
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            return response('OK');
        }
    }

    /**
     * Handle /start — buka registrasi baru, tanya nama lengkap.
     */
    protected function handleStart($chatId, $telegramId, $username, $firstName)
    {
        $employee = Employee::where('telegram_id', $telegramId)->first();

        if ($employee) {
            if ($employee->is_active) {
                return $this->sendMessage($chatId, "Halo $firstName! Akun kamu sudah aktif. Silakan kirim laporan harian.");
            }
            return $this->sendMessage($chatId, 'Akun kamu masih belum aktif. Silakan hubungi admin.');
        }

        // Hapus registrasi pending sebelumnya yang belum selesai
        BotRegistration::where('telegram_id', $telegramId)
            ->where('status', 'pending')
            ->delete();

                BotRegistration::create([
            'telegram_id'       => $telegramId,
            'telegram_username' => $username,
            'name'              => $firstName,
            'status'            => 'pending',
            'step'              => 'waiting_name',
        ]);

        return $this->sendMessage(
            $chatId,
            "Halo! Silakan kirim *nama lengkap* kamu."
        );
    }

    /**
     * Handle input nama dari user — simpan lalu tanya jabatan.
     */
    protected function handleNameInput($chatId, BotRegistration $registration, string $name)
    {
        $name = trim($name);
        if (strlen($name) < 2) {
            return $this->sendMessage($chatId, 'Nama terlalu pendek. Silakan kirim nama lengkap kamu.');
        }

        $registration->update([
            'name' => $name,
            'step' => 'waiting_jabatan',
        ]);

        // Keyboard inline untuk pilihan jabatan
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Teknisi', 'callback_data' => 'jabatan:teknisi'],
                ],
                [
                    ['text' => 'Foreman', 'callback_data' => 'jabatan:foreman'],
                ],
                [
                    ['text' => 'Supervisor', 'callback_data' => 'jabatan:supervisor'],
                ],
            ]
        ];

        return $this->sendMessageWithKeyboard($chatId, "Terima kasih *{$name}*! Pilih jabatan kamu:", $keyboard);
    }

    /**
     * Handle input jabatan dari user (teks biasa: "teknisi", "foreman", "supervisor").
     */
    protected function handleJabatanInput($chatId, BotRegistration $registration, string $jabatan)
    {
        $jabatan = strtolower(trim($jabatan));
        $valid = ['teknisi', 'foreman', 'supervisor'];

        if (!in_array($jabatan, $valid)) {
            return $this->sendMessage(
                $chatId,
                "Jabatan tidak valid. Pilih salah satu: Teknisi, Foreman, atau Supervisor."
            );
        }

        $registration->update([
            'requested_jabatan' => $jabatan,
            'step'              => null,
        ]);

        return $this->sendMessage(
            $chatId,
            "Pendaftaran kamu sudah diterima. Silakan tunggu persetujuan dari admin."
        );
    }

    protected function handleReport($chatId, Employee $employee, string $text)
    {
        if (preg_match('/^NIK\s+(\S+)$/i', $text, $matches)) {
            $nik = $matches[1];

            $registration = BotRegistration::where('telegram_id', $employee->telegram_id)
                ->latest()
                ->first();

            if ($registration && $registration->status === 'pending') {
                $registration->update(['nik' => $nik]);

                $found = Employee::where('name', $registration->name)->first();
                if ($found) {
                    $found->update([
                        'telegram_id'       => $employee->telegram_id,
                        'telegram_username' => $registration->telegram_username,
                    ]);
                }

                return $this->sendMessage(
                    $chatId,
                    "Terima kasih! NIK kamu ($nik) sudah tercatat. Admin akan memproses pendaftaran kamu segera."
                );
            }

            return $this->sendMessage($chatId, 'Pendaftaran kamu sedang diproses atau sudah selesai.');
        }

        $analysis = $this->aiService->analyzeReportText($text);

        $jenis = 'corrective';
        if (!empty($analysis['report_type']) && $analysis['report_type'] === 'preventive') {
            $jenis = 'preventive';
        }

        $report = MaintenanceReport::create([
            'reported_by'        => $employee->id,
            'shift'              => $employee->shift ?? 'reguler',
            'tanggal'            => now()->toDateString(),
            'jenis'              => $jenis,
            'deskripsi_masalah'  => $text,
            'status'             => 'open',
            'ai_suggestion_json' => $analysis,
        ]);

        if (!empty($analysis['suggested_asset'])) {
            $asset = Asset::where('description', 'like', '%' . $analysis['suggested_asset'] . '%')->first();
            if ($asset) {
                $report->update(['asset_id' => $asset->id]);
            } else {
                BotUnknownAsset::create([
                    'report_id'        => $report->id,
                    'keyword_mentioned' => $analysis['suggested_asset'],
                ]);
            }
        }

        $response  = "Laporan diterima!\n\n";
        $response .= "ID Laporan: #{$report->id}\n";
        $response .= "Tanggal: " . now()->format('d/m/Y') . "\n";

        if (!empty($analysis['confidence'])) {
            $response .= "AI Confidence: {$analysis['confidence']}%\n";
        }

        $response .= "\n" . ($analysis['message'] ?? 'Laporan akan direview oleh admin.');

        return $this->sendMessage($chatId, $response);
    }

        protected function sendMessage($chatId, string $text)
    {
        $token = config('telegram.bot_token');

        if (empty($token)) {
            Log::info("[Telegram Mock] Would send to $chatId: $text");
            return response('OK');
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]);

            if (!$response->successful()) {
                Log::warning("TelegramWebhook sendMessage failed: " . $response->body());
            }

            return response('OK');
        } catch (\Exception $e) {
            Log::error("TelegramWebhook sendMessage exception: " . $e->getMessage());
            return response('OK');
        }
    }

    /**
     * Kirim pesan dengan inline keyboard (untuk pilihan jabatan).
     */
    protected function sendMessageWithKeyboard($chatId, string $text, array $keyboard)
    {
        $token = config('telegram.bot_token');

        if (empty($token)) {
            Log::info("[Telegram Mock] Would send to $chatId with keyboard: $text");
            return response('OK');
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'      => $chatId,
                'text'         => $text,
                'parse_mode'   => 'Markdown',
                'reply_markup' => json_encode($keyboard),
            ]);

            if (!$response->successful()) {
                Log::warning("TelegramWebhook sendMessageWithKeyboard failed: " . $response->body());
            }

            return response('OK');
        } catch (\Exception $e) {
            Log::error("TelegramWebhook sendMessageWithKeyboard exception: " . $e->getMessage());
            return response('OK');
        }
    }
}
