<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\WeatherReading;
use App\Services\SerialPortManager;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SerialLiveController extends Controller
{
    public function show(SerialPortManager $manager): View
    {
        try {
            $settings = AppSetting::query()
                ->whereIn('key', ['weather.serial.port', 'weather.serial.baud', 'weather.serial.source'])
                ->pluck('value', 'key');
        } catch (QueryException) {
            $settings = collect();
        }

        $configuredPort = $settings['weather.serial.port'] ?? env('WEATHER_SERIAL_PORT', '');

        $serialStatus = [
            'listenerRunning' => $manager->isListenerRunning(),
            'configuredPort' => $configuredPort,
        ];

        try {
            $latest = WeatherReading::query()->latest('recorded_at')->first();
        } catch (QueryException) {
            $latest = null;
        }

        return view('live', [
            'latest' => $latest,
            'serialStatus' => $serialStatus,
        ]);
    }

    public function fetch(SerialPortManager $manager): JsonResponse
    {
        try {
            $settings = AppSetting::query()
                ->whereIn('key', ['weather.serial.port', 'weather.serial.baud'])
                ->pluck('value', 'key');
        } catch (QueryException) {
            $settings = collect();
        }

        $port = $settings['weather.serial.port'] ?? env('WEATHER_SERIAL_PORT', '');
        $baud = (int) ($settings['weather.serial.baud'] ?? env('WEATHER_SERIAL_BAUD', 9600));

        if (! is_string($port) || trim($port) === '') {
            return response()->json(['error' => 'Puerto serial no configurado. Ve a /hardware y selecciona un puerto.'], 503);
        }

        if (! file_exists($port)) {
            return response()->json(['error' => "El puerto {$port} no existe. Verifica que el Arduino esté conectado."], 503);
        }

        // If our DB listener is running, the port is in use.
        if ($manager->isListenerRunning()) {
            return response()->json([
                'error' => 'El listener de base de datos está activo.',
                'hint' => 'Detenlo en /hardware para usar el modo tiempo real, o ve a /monitor para ver los datos guardados.',
            ], 503);
        }

        $payload = $manager->readLine($port, $baud, 8);

        if ($payload === null) {
            return response()->json(['error' => 'No se recibieron datos validos del puerto serial en 8 segundos.'], 504);
        }

        return response()->json([
            'ok' => true,
            'data' => $payload,
        ]);
    }
}
