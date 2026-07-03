<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== AI PROVIDERS ===\n";
foreach (App\Models\AiProvider::all() as $p) {
    echo "ID:{$p->id} | Nama:{$p->name} | Type:{$p->provider_type} | Status:{$p->status} | DailyLimit:{$p->daily_token_limit} | UsedToday:{$p->tokens_used_today} | LastUsed:" . ($p->last_used_at ?? 'never') . "\n";
}

echo "\n=== AI USAGE LOGS ===\n";
echo 'Count: ' . App\Models\AiUsageLog::count() . "\n";
if (App\Models\AiUsageLog::count() > 0) {
    foreach (App\Models\AiUsageLog::latest()->take(10)->get() as $l) {
        echo "Log#{$l->id} | Provider:" . ($l->provider_id ?? '-') . " | Tokens:{$l->tokens_used} | Type:{$l->request_type} | Status:{$l->status} | Created:{$l->created_at}\n";
    }
}

echo "\n=== TELEGRAM ===\n";
echo 'Bot Token: ' . (config('telegram.bot_token') ? 'ADA (' . substr(config('telegram.bot_token'), 0, 8) . '...)' : 'KOSONG') . "\n";
echo 'WEBHOOK route: ' . (app('router')->has('telegram.webhook') ? 'TERDAFTAR' : 'TIDAK ADA') . "\n";

echo "\n=== POLL COMMAND ===\n";
$pollCmd = 'App\Console\Commands\PollTelegramUpdates';
echo $pollCmd . ': ' . (class_exists($pollCmd) ? 'ADA' : 'TIDAK ADA') . "\n";
