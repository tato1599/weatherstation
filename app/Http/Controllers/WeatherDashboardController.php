<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\WeatherReading;
use App\Services\SerialPortManager;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class WeatherDashboardController extends Controller
{
    public function landing(): View
    {
        $data = $this->buildViewData();

        return view('landing', $data);
    }

    public function monitor(): View
    {
        $data = $this->buildViewData();


        return view('dashboard', $data);
    }

    public function hardware(): View
    {
        $data = $this->buildViewData();

        return view('hardware', $data);
    }

    private function buildViewData(): array
    {
        try {
            $latest = WeatherReading::query()->latest('recorded_at')->first();

            $history = WeatherReading::query()
                ->latest('recorded_at')
                ->paginate(25);

            $chartRows = WeatherReading::query()
                ->where('recorded_at', '>=', now()->subHours(5))
                ->orderBy('recorded_at')
                ->get(['recorded_at', 'temperature_c', 'temperature_bmp280_c', 'humidity_percent', 'pressure_hpa', 'altitude_m']);
        } catch (QueryException) {
            $latest = null;
            $history = new LengthAwarePaginator([], 0, 25);
            $chartRows = collect();
        }

        try {
            $settings = AppSetting::query()
                ->whereIn('key', ['weather.serial.port', 'weather.serial.baud', 'weather.serial.source'])
                ->pluck('value', 'key');
        } catch (QueryException) {
            $settings = collect();
        }

        $serialManager = app(SerialPortManager::class);
        $configuredPort = $settings['weather.serial.port'] ?? env('WEATHER_SERIAL_PORT', '');

        $serialStatus = [
            'listenerRunning' => $serialManager->isListenerRunning(),
            'portInUse' => is_string($configuredPort) && $configuredPort !== '' ? $serialManager->isPortInUse($configuredPort) : false,
            'portProcesses' => is_string($configuredPort) && $configuredPort !== '' ? $serialManager->getPortProcesses($configuredPort) : [],
            'configuredPort' => $configuredPort,
        ];

        return [
            'latest' => $latest,
            'history' => $history,
            'serialPorts' => $serialManager->listPorts(),
            'serialConfig' => [
                'port' => $configuredPort,
                'baud' => (int) ($settings['weather.serial.baud'] ?? env('WEATHER_SERIAL_BAUD', 9600)),
                'source' => $settings['weather.serial.source'] ?? 'serial',
            ],
            'serialStatus' => $serialStatus,
            'chart' => [
                'labels' => $chartRows->pluck('recorded_at')->map(fn($value) => $value->format('H:i'))->values(),
                'temperature' => $chartRows->pluck('temperature_c')->values(),
                'temperature_bmp280' => $chartRows->pluck('temperature_bmp280_c')->values(),
                'humidity' => $chartRows->pluck('humidity_percent')->values(),
                'pressure' => $chartRows->pluck('pressure_hpa')->values(),
            ],
        ];
    }
}
