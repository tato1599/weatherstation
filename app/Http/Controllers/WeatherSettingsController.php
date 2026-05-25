<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class WeatherSettingsController extends Controller
{
    public function updateSerial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'serial_port' => ['required', 'string', 'max:255'],
            'serial_baud' => ['required', 'integer', 'between:1200,115200'],
            'serial_source' => ['required', 'string', 'max:50'],
        ]);

        $this->save('weather.serial.port', $validated['serial_port']);
        $this->save('weather.serial.baud', (string) $validated['serial_baud']);
        $this->save('weather.serial.source', $validated['serial_source']);

        return back()->with('status', 'Configuracion serial guardada.');
    }

    private function save(string $key, ?string $value): void
    {
        AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function startSerialListener(): RedirectResponse
    {
        $port = $this->setting('weather.serial.port', env('WEATHER_SERIAL_PORT', ''));
        $baud = $this->setting('weather.serial.baud', env('WEATHER_SERIAL_BAUD', '9600'));

        if (! is_string($port) || trim($port) === '') {
            return back()->with('status', 'No se pudo iniciar: puerto serial no configurado.');
        }

        $php = PHP_BINARY;

        $command = sprintf(
            'pkill -f %s; nohup %s artisan weather:serial-listen --port=%s --baud=%s > %s 2>&1 < /dev/null &',
            escapeshellarg('[w]eather:serial-listen'),
            escapeshellarg($php),
            escapeshellarg($port),
            escapeshellarg((string) $baud),
            escapeshellarg(storage_path('logs/serial-listener.log')),
        );

        Process::path(base_path())->run($command);

        return back()->with('status', "Escucha serial iniciada en {$port} a {$baud} baudios.");
    }

    public function stopSerialListener(): RedirectResponse
    {
        Process::path(base_path())->run('pkill -f '.escapeshellarg('[w]eather:serial-listen'));

        return back()->with('status', 'Escucha serial detenida.');
    }

    public function restartSerialListener(): RedirectResponse
    {
        $port = $this->setting('weather.serial.port', env('WEATHER_SERIAL_PORT', ''));
        $baud = $this->setting('weather.serial.baud', env('WEATHER_SERIAL_BAUD', '9600'));

        if (! is_string($port) || trim($port) === '') {
            return back()->with('status', 'No se pudo reiniciar: puerto serial no configurado.');
        }

        $php = PHP_BINARY;
        $logPath = storage_path('logs/serial-listener.log');

        // 1. Kill our own listener
        Process::path(base_path())->run('pkill -f '.escapeshellarg('[w]eather:serial-listen'));

        // 2. Small delay to let OS release handles
        usleep(500000); // 500ms

        // 3. Kill any other process using the port
        $lsofResult = Process::path(base_path())->run('lsof -t '.escapeshellarg($port));
        if ($lsofResult->successful() && trim($lsofResult->output()) !== '') {
            $pids = array_filter(explode("\n", trim($lsofResult->output())));
            foreach ($pids as $pid) {
                if (is_numeric($pid)) {
                    Process::path(base_path())->run('kill -9 '.(int) $pid);
                }
            }
        }

        // 4. Another small delay
        usleep(300000); // 300ms

        // 5. Start fresh listener
        $command = sprintf(
            'nohup %s artisan weather:serial-listen --port=%s --baud=%s > %s 2>&1 < /dev/null &',
            escapeshellarg($php),
            escapeshellarg($port),
            escapeshellarg((string) $baud),
            escapeshellarg($logPath),
        );

        Process::path(base_path())->run($command);

        return back()->with('status', "Puerto reiniciado y escucha serial iniciada en {$port} a {$baud} baudios.");
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $value = AppSetting::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }
}
