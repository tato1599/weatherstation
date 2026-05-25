<?php

namespace App\Services;

use App\Models\WeatherReading;
use Carbon\CarbonImmutable;

class WeatherReadingIngestor
{
    public function ingest(array $payload, string $source, array $meta = []): WeatherReading
    {
        return WeatherReading::create([
            'temperature_c' => (float) $payload['temperature_c'],
            'temperature_bmp280_c' => isset($payload['temperature_bmp280_c']) ? (float) $payload['temperature_bmp280_c'] : null,
            'humidity_percent' => (float) $payload['humidity_percent'],
            'pressure_hpa' => (float) ($payload['pressure_hpa'] ?? 1013.25),
            'altitude_m' => isset($payload['altitude_m']) ? (float) $payload['altitude_m'] : null,
            'source' => $source,
            'recorded_at' => isset($payload['recorded_at'])
                ? CarbonImmutable::parse($payload['recorded_at'])
                : now(),
            'meta' => $meta ?: null,
        ]);
    }
}
