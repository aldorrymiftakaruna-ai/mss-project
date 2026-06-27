<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\CmController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DssController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::resource('assets', AssetController::class);
Route::resource('maintenance', MaintenanceController::class);
Route::resource('cm', CmController::class);
Route::resource('spareparts', SparePartController::class);
Route::resource('employees', EmployeeController::class);
Route::get('/dss', [DssController::class, 'index'])->name('dss.index');