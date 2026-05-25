<?php

use App\Http\Controllers\SerialLiveController;
use App\Http\Controllers\WeatherDashboardController;
use App\Http\Controllers\WeatherSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WeatherDashboardController::class, 'landing'])->name('landing');
Route::get('/monitor', [WeatherDashboardController::class, 'monitor'])->name('dashboard');
Route::get('/hardware', [WeatherDashboardController::class, 'hardware'])->name('hardware');
Route::get('/live', [SerialLiveController::class, 'show'])->name('live');
Route::get('/api/v1/serial/live', [SerialLiveController::class, 'fetch'])->name('api.serial.live');

Route::post('/settings/serial', [WeatherSettingsController::class, 'updateSerial'])->name('settings.serial.update');
Route::post('/settings/serial/listener/start', [WeatherSettingsController::class, 'startSerialListener'])->name('settings.serial.listener.start');
Route::post('/settings/serial/listener/stop', [WeatherSettingsController::class, 'stopSerialListener'])->name('settings.serial.listener.stop');
Route::post('/settings/serial/listener/restart', [WeatherSettingsController::class, 'restartSerialListener'])->name('settings.serial.listener.restart');
