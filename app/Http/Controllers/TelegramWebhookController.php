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

                $employee = Employee::where('telegram_id', $telegramId)
                    ->where('is_active', true)
                    ->first();

                if (!$employee) {
                    return $this->sendMessage($chatId, 'Maaf, akun kamu belum terdaftar atau belum disetujui. Silakan hubungi admin.');
                }

                return $this->handleReport($chatId, $employee, $text);
            }

            return response('OK');
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            return response('OK');
        }
    }

    protected function handleStart($chatId, $telegramId, $username, $firstName)
    {
        $employee = Employee::where('telegram_id', $telegramId)->first();

        if ($employee) {
            if ($employee->is_active) {
                return $this->sendMessage($chatId, "Halo $firstName! Akun kamu sudah aktif. Silakan kirim laporan harian.");
            }
            return $this->sendMessage($chatId, 'Akun kamu masih belum aktif. Silakan hubungi admin.');
        }

        $pending = BotRegistration::where('telegram_id', $telegramId)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return $this->sendMessage($chatId, 'Pendaftaran kamu masih diproses. Silakan tunggu konfirmasi dari admin.');
        }

        BotRegistration::create([
            'telegram_id'       => $telegramId,
            'telegram_username' => $username,
            'name'              => $firstName,
            'status'            => 'pending',
        ]);

        return $this->sendMessage(
            $chatId,
            "Halo $firstName! Untuk mendaftar sebagai teknisi, silakan kirim NIK kamu.\n\nContoh: NIK 123456"
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
}
