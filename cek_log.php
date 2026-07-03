<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== AI USAGE LOGS (3 terakhir) ===\n";
foreach (App\Models\AiUsageLog::latest()->take(3)->get() as $l) {
    echo "Log#{$l->id} | Type:{$l->request_type} | Tokens:{$l->tokens_used} | Status:{$l->status} | Error:{$l->error_message} | Created:{$l->created_at}\n";
}

echo "\n=== PROVIDER ===\n";
foreach (App\Models\AiProvider::all() as $p) {
    echo "{$p->name} | Status:{$p->status} | UsedToday:{$p->tokens_used_today} | LastHealthCheck:{$p->last_health_check}\n";
}

echo "\n=== REPORTS WITH ai_suggestion ===\n";
$reports = App\Models\MaintenanceReport::whereNotNull('ai_suggestion_json')->get();
echo 'Total: ' . $reports->count() . "\n";
foreach ($reports as $r) {
    $analysis = $r->ai_suggestion_json;
    echo "Report#{$r->id} | Code:{$r->report_code} | AssetID:{$r->asset_id} | AI_asset:{$analysis['detected_asset']} | Duration:{$r->work_duration_minutes} | RootCause:{$r->root_cause}\n";
}

echo "\n=== RECENT LOG FILE (80 baris terakhir) ===\n";
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $handle = fopen($logFile, 'r');
    if ($handle) {
        fseek($handle, 0, SEEK_END);
        $size = ftell($handle);
        $pos = max(0, $size - 5000);
        fseek($handle, $pos);
        $content = fread($handle, $size - $pos);
        fclose($handle);
        $lines = explode("\n", $content);
        $lines = array_slice($lines, -80);
        echo implode("\n", $lines);
    }
} else {
    echo "File log tidak ditemukan.\n";
}
