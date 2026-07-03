<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceControlsController;
use App\Http\Controllers\DoseModeController;
use App\Http\Controllers\LiveStatusController;
use App\Http\Controllers\MedicineModeController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/esp32/test-connection', [DashboardController::class, 'testConnection'])->name('esp32.test-connection');

Route::get('/mode-selection', [ModeController::class, 'index'])->name('mode-selection');
Route::put('/mode-selection', [ModeController::class, 'update'])->name('mode-selection.update');

Route::get('/dose-mode', [DoseModeController::class, 'index'])->name('dose-mode');
Route::put('/dose-mode', [DoseModeController::class, 'update'])->name('dose-mode.update');

Route::get('/medicine-mode', [MedicineModeController::class, 'index'])->name('medicine-mode');
Route::get('/medicine-mode/create', [MedicineModeController::class, 'create'])->name('medicine-mode.create');
Route::post('/medicine-mode', [MedicineModeController::class, 'store'])->name('medicine-mode.store');
Route::post('/medicine-mode/sync', [MedicineModeController::class, 'sync'])->name('medicine-mode.sync');
Route::get('/medicine-mode/{id}/edit', [MedicineModeController::class, 'edit'])->name('medicine-mode.edit');
Route::put('/medicine-mode/{id}', [MedicineModeController::class, 'update'])->name('medicine-mode.update');
Route::delete('/medicine-mode/{id}', [MedicineModeController::class, 'destroy'])->name('medicine-mode.destroy');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

Route::get('/device-controls', [DeviceControlsController::class, 'index'])->name('device-controls');
Route::post('/device-controls/sync-time', [DeviceControlsController::class, 'syncTime'])->name('device-controls.sync-time');
Route::post('/device-controls/restart', [DeviceControlsController::class, 'restartDevice'])->name('device-controls.restart');
Route::get('/device-controls/refresh-status', [DeviceControlsController::class, 'refreshStatus'])->name('device-controls.refresh-status');

Route::get('/live-status', [LiveStatusController::class, 'index'])->name('live-status');
Route::get('/live-status/poll', [LiveStatusController::class, 'poll'])->name('live-status.poll');
