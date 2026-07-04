<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\CmController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DssController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AiProviderController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\AhpController;
use App\Http\Controllers\PredictiveController;
use App\Http\Controllers\CostController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\IntegratedDssController;
use App\Http\Controllers\WeibullController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Telegram webhook â€” tanpa auth, dipanggil langsung oleh server Telegram
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');

Route::middleware('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/assets/import', [AssetController::class, 'importForm'])->name('assets.import.form');
    Route::post('/assets/import', [AssetController::class, 'import'])->name('assets.import');
    Route::post('/assets/{asset}/spare-parts', [AssetController::class, 'attachSparePart'])->name('assets.spareparts.attach');
    Route::delete('/assets/{asset}/spare-parts/{sparePart}', [AssetController::class, 'detachSparePart'])->name('assets.spareparts.detach');
    Route::get('/assets/bom/import', [AssetController::class, 'bomImportForm'])->name('assets.bom.import.form');
    Route::post('/assets/bom/import', [AssetController::class, 'bomImport'])->name('assets.bom.import');
    Route::get('/assets/bom/template', [AssetController::class, 'bomTemplate'])->name('assets.bom.template');
    Route::resource('assets', AssetController::class);
    Route::resource('maintenance', MaintenanceController::class);
    Route::post('/maintenance/{maintenance}/manpower', [MaintenanceController::class, 'addManpower'])->name('maintenance.manpower.store');
    Route::put('/maintenance/{maintenance}/metrics', [MaintenanceController::class, 'updateMetrics'])->name('maintenance.metrics.update');


    Route::get('/cm/template', [CmController::class, 'downloadTemplate'])->name('cm.template');
    Route::get('/cm/import', [CmController::class, 'importForm'])->name('cm.import.form');
    Route::post('/cm/import', [CmController::class, 'import'])->name('cm.import');
    Route::get('/cm/trend-data', [CmController::class, 'trendData'])->name('cm.trend-data');
    Route::get('/cm/findings/template', [CmController::class, 'downloadTemplateFinding'])->name('cm.findings.template');
    Route::get('/cm/findings/import', [CmController::class, 'importFindingForm'])->name('cm.findings.import.form');
    Route::post('/cm/findings/import', [CmController::class, 'importFinding'])->name('cm.findings.import');
    Route::get('/cm/findings/{cmFinding}', [CmController::class, 'showFinding'])->name('cm.findings.show');
    Route::delete('/cm/findings/{cmFinding}', [CmController::class, 'destroyFinding'])->name('cm.findings.destroy');
    Route::put('/cm/findings/{cmFinding}', [CmController::class, 'updateFinding'])->name('cm.findings.update');
    Route::resource('cm', CmController::class)->except(['show']);

    Route::get('/spareparts/template', [SparePartController::class, 'downloadTemplate'])->name('spareparts.template');
    Route::get('/spareparts/import', [SparePartController::class, 'importForm'])->name('spareparts.import.form');
    Route::post('/spareparts/import', [SparePartController::class, 'import'])->name('spareparts.import');
    Route::post('/spareparts/{sparepart}/images', [SparePartController::class, 'uploadImage'])->name('spareparts.images.upload');
    Route::delete('/spareparts/{sparepart}/images/{image}', [SparePartController::class, 'deleteImage'])->name('spareparts.images.delete');
    Route::get('/spareparts/update-stok', [SparePartController::class, 'updateStokForm'])->name('spareparts.updateStok.form');
    Route::post('/spareparts/update-stok', [SparePartController::class, 'updateStok'])->name('spareparts.updateStok');
    Route::resource('spareparts', SparePartController::class);
        Route::resource('employees', EmployeeController::class);
    // DSS deskriptif dialihkan ke dashboard (informasi sudah digabung)
    Route::get('/dss', function () {
        return redirect()->route('dashboard');
    })->name('dss.index');

    // DSS Terintegrasi (Waterfall)
    Route::get('/dss/integrated', [IntegratedDssController::class, 'index'])->name('dss.integrated');
    Route::post('/dss/integrated/recalculate', [IntegratedDssController::class, 'recalculate'])->name('dss.integrated.recalculate');

    // AHP + TOPSIS
    Route::get('/ahp', [AhpController::class, 'index'])->name('ahp.index');
    Route::get('/ahp/create', [AhpController::class, 'create'])->name('ahp.create');
    Route::post('/ahp', [AhpController::class, 'store'])->name('ahp.store');
    Route::get('/ahp/{ahpSession}/pairwise', [AhpController::class, 'pairwise'])->name('ahp.pairwise');
    Route::post('/ahp/{ahpSession}/pairwise', [AhpController::class, 'storePairwise'])->name('ahp.storePairwise');
    Route::get('/ahp/{ahpSession}/result', [AhpController::class, 'result'])->name('ahp.result');
    Route::get('/ahp/{ahpSession}/ranking', [AhpController::class, 'ranking'])->name('ahp.ranking');
            Route::delete('/ahp/{ahpSession}', [AhpController::class, 'destroy'])->name('ahp.destroy');

            // Predictive Risk
    Route::get('/predictive', [PredictiveController::class, 'index'])->name('predictive.index');
    Route::get('/predictive/{asset}', [PredictiveController::class, 'detail'])->name('predictive.detail');
    Route::post('/predictive/recalculate', [PredictiveController::class, 'recalculate'])->name('predictive.recalculate');
        Route::post('/predictive/{asset}/recalculate', [PredictiveController::class, 'recalculateAsset'])->name('predictive.recalculate-asset');

    // Weibull Reliability
    Route::get('/weibull', [WeibullController::class, 'index'])->name('weibull.index');
    Route::get('/weibull/{asset}', [WeibullController::class, 'detail'])->name('weibull.detail');
    Route::post('/weibull/calculate-all', [WeibullController::class, 'calculateAll'])->name('weibull.calculate-all');
    Route::post('/weibull/{asset}/calculate', [WeibullController::class, 'calculateAsset'])->name('weibull.calculate-asset');

    // Forecasting
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast.index');
    Route::get('/forecast/calculate', [ForecastController::class, 'calculate'])->name('forecast.calculate');

    // Cost Analysis
    Route::get('/cost', [CostController::class, 'index'])->name('cost.index');
    Route::get('/cost/settings', [CostController::class, 'settings'])->name('cost.settings');
    Route::post('/cost/rates', [CostController::class, 'updateRates'])->name('cost.rates.update');
    Route::get('/cost/reanalyze-all', [CostController::class, 'reanalyzeAll'])->name('cost.reanalyze-all');
    Route::get('/cost/reanalyze/{reportId}', [CostController::class, 'reanalyze'])->name('cost.reanalyze');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // AI Providers
    Route::get('/ai-providers', [AiProviderController::class, 'index'])->name('ai-providers.index');
    Route::post('/ai-providers', [AiProviderController::class, 'store'])->name('ai-providers.store');
    // Static action routes â€” WAJIB sebelum {aiProvider} wildcard
    Route::post('/ai-providers/test-all', [AiProviderController::class, 'testAll'])->name('ai-providers.test-all');
    Route::post('/ai-providers/reset-quota', [AiProviderController::class, 'resetQuota'])->name('ai-providers.reset-quota');
    // Route alias
    Route::post('/ai-providers/aliases/{alias}/confirm', [AiProviderController::class, 'confirmAlias'])->name('ai-providers.aliases.confirm');
    Route::post('/ai-providers/aliases/{alias}/reject', [AiProviderController::class, 'rejectAlias'])->name('ai-providers.aliases.reject');
    // Wildcard provider routes â€” setelah static routes
    Route::put('/ai-providers/{aiProvider}', [AiProviderController::class, 'update'])->name('ai-providers.update');
    Route::delete('/ai-providers/{aiProvider}', [AiProviderController::class, 'destroy'])->name('ai-providers.destroy');
    Route::post('/ai-providers/{aiProvider}/test', [AiProviderController::class, 'test'])->name('ai-providers.test');

    // Bot Telegram Panel
    Route::get('/bot', [BotController::class, 'index'])->name('bot.index');
    Route::post('/bot/settings', [BotController::class, 'updateSettings'])->name('bot.settings');
    Route::post('/bot/test-connection', [BotController::class, 'testConnection'])->name('bot.test-connection');
    Route::post('/bot/set-webhook', [BotController::class, 'setWebhook'])->name('bot.set-webhook');
    Route::post('/bot/delete-webhook', [BotController::class, 'deleteWebhook'])->name('bot.delete-webhook');
    Route::post('/bot/registrations/{registration}/approve', [BotController::class, 'approveRegistration'])->name('bot.registrations.approve');
    Route::post('/bot/registrations/{registration}/reject', [BotController::class, 'rejectRegistration'])->name('bot.registrations.reject');
    Route::post('/bot/polling/start', [BotController::class, 'startPolling'])->name('bot.polling-start');
    Route::post('/bot/polling/stop', [BotController::class, 'stopPolling'])->name('bot.polling-stop');
    Route::get('/bot/polling/status', [BotController::class, 'pollingStatus'])->name('bot.polling-status');

});

