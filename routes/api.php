<?php

use App\Http\Controllers\WeatherReadingApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('ingest.token')->group(function () {
    Route::post('/v1/readings', [WeatherReadingApiController::class, 'store']);
});
