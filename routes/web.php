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

Route::get('/', function () {
    return redirect()->route('dashboard');
});

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

Route::get('/cm/template', [CmController::class, 'downloadTemplate'])->name('cm.template');
Route::get('/cm/import', [CmController::class, 'importForm'])->name('cm.import.form');
Route::post('/cm/import', [CmController::class, 'import'])->name('cm.import');
Route::get('/cm/trend-data', [CmController::class, 'trendData'])->name('cm.trend-data');
Route::get('/cm/findings/template', [CmController::class, 'downloadTemplateFinding'])->name('cm.findings.template');
Route::get('/cm/findings/import', [CmController::class, 'importFindingForm'])->name('cm.findings.import.form');
Route::post('/cm/findings/import', [CmController::class, 'importFinding'])->name('cm.findings.import');
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
Route::get('/dss', [DssController::class, 'index'])->name('dss.index');
Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');