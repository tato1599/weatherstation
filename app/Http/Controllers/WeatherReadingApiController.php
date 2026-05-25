<?php

namespace App\Http\Controllers;

use App\Services\WeatherReadingIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherReadingApiController extends Controller
{
    public function store(Request $request, WeatherReadingIngestor $ingestor): JsonResponse
    {
        $validated = $request->validate([
            'temperature_c' => ['required', 'numeric', 'between:-50,100'],
            'temperature_bmp280_c' => ['nullable', 'numeric', 'between:-50,100'],
            'humidity_percent' => ['required', 'numeric', 'between:0,100'],
            'pressure_hpa' => ['required', 'numeric', 'between:300,1200'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $reading = $ingestor->ingest(
            payload: $validated,
            source: 'api',
            meta: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        return response()->json([
            'ok' => true,
            'id' => $reading->id,
            'recorded_at' => $reading->recorded_at?->toIso8601String(),
        ], 201);
    }
}
