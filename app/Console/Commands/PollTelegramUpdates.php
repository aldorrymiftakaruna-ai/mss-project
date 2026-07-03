<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\TelegramMessageHandlerTrait;
use App\Console\Commands\Traits\TelegramSenderTrait;
use App\Services\Telegram\ReportWizardService;
use App\Services\Telegram\PhotoStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollTelegramUpdates extends Command
{
    use TelegramMessageHandlerTrait;
    use TelegramSenderTrait;

    protected $signature = 'telegram:poll'
        . ' {--timeout=30 : Long polling timeout seconds}'
        . ' {--limit=10 : Max updates per request}';

    protected $description = 'Jalankan long polling Telegram untuk menerima update bot';

    protected ReportWizardService $reportWizard;
    protected PhotoStorageService $photoStorage;
    protected int $lastUpdateId = 0;

    public function __construct(ReportWizardService $reportWizard, PhotoStorageService $photoStorage)
    {
        parent::__construct();
        $this->reportWizard = $reportWizard;
        $this->photoStorage = $photoStorage;
    }

    public function handle(): void
    {
        $token   = config('telegram.bot_token');
        $timeout = (int) $this->option('timeout');
        $limit   = (int) $this->option('limit');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN belum dikonfigurasi di .env');
            return;
        }

        $this->info('Memulai long polling Telegram...');
        $this->line('Tekan Ctrl+C untuk berhenti.');
        $this->newLine();

        $lockFile = storage_path('app/telegram_poll.lock');
        file_put_contents($lockFile, time());
        $stopFile = storage_path('app/telegram_poll.stop');

        while (true) {
            if (file_exists($stopFile)) {
                $this->info('Stop signal diterima. Menghentikan polling...');
                @unlink($stopFile);
                break;
            }

            file_put_contents($lockFile, time());

            try {
                $response = Http::timeout($timeout + 5)
                    ->post("https://api.telegram.org/bot{$token}/getUpdates", [
                        'offset'  => $this->lastUpdateId + 1,
                        'timeout' => $timeout,
                        'limit'   => $limit,
                        'allowed_updates' => ['message', 'callback_query'],
                    ]);

                if (!$response->successful()) {
                    $this->warn('getUpdates gagal: ' . $response->body());
                    sleep(2);
                    continue;
                }

                $updates = $response->json('result', []);

                foreach ($updates as $update) {
                    $updateId = $update['update_id'] ?? 0;
                    if (isset($update['message'])) {
                        $this->processUpdate($update);
                    }
                    if (isset($update['callback_query'])) {
                        $this->processCallbackQuery($update);
                    }
                    if ($updateId > $this->lastUpdateId) {
                        $this->lastUpdateId = $updateId;
                    }
                }

                if (empty($updates)) {
                    usleep(200000);
                }
            } catch (\Exception $e) {
                $this->warn('Error: ' . $e->getMessage());
                Log::error('PollTelegramUpdates exception', ['error' => $e->getMessage()]);
                sleep(2);
            }
        }

        @unlink($lockFile);
        $this->info('Polling dihentikan.');
    }

    private function processCallbackQuery(array $update): void
    {
        $callback   = $update['callback_query'];
        $callbackId = $callback['id'] ?? '';
        $message    = $callback['message'] ?? [];
        $chatId     = $message['chat']['id'] ?? 0;
        $messageId  = $message['message_id'] ?? 0;
        $data       = $callback['data'] ?? '';
        $from       = $callback['from'] ?? [];
        $telegramId = (string) ($from['id'] ?? '');
        $firstName  = $from['first_name'] ?? '';

        $this->line("Callback dari {$firstName}: {$data}");
        $this->answerCallbackQuery($callbackId);

        $employee = \App\Models\Employee::where('telegram_id', $telegramId)
            ->where('is_active', true)
            ->first();

        if (!$employee) {
            $this->editMessageTextSimple($chatId, $messageId, 'Akun kamu tidak ditemukan atau belum aktif.');
            return;
        }

        if (str_starts_with($data, 'wizard:') || str_starts_with($data, 'equipment_candidate:') || str_starts_with($data, 'work_type:')) {
            $this->handleWizardCallback($chatId, $messageId, $data);
        }
    }
}
