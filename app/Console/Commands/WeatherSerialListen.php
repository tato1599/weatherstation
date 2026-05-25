<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Services\WeatherReadingIngestor;
use Illuminate\Console\Command;

class WeatherSerialListen extends Command
{
    protected $signature = 'weather:serial-listen
                            {--port= : Serial port path, example /dev/tty.usbmodem14101}
                            {--baud=9600 : Baud rate}
                            {--source=serial : Data source label}';

    protected $description = 'Listen to weather readings from a serial port (JSON or text)';

    public function handle(WeatherReadingIngestor $ingestor): int
    {
        $port = (string) ($this->option('port') ?: $this->setting('weather.serial.port', env('WEATHER_SERIAL_PORT', '')));
        $baud = (int) ($this->option('baud') ?: $this->setting('weather.serial.baud', env('WEATHER_SERIAL_BAUD', 9600)));
        $source = (string) ($this->option('source') ?: $this->setting('weather.serial.source', 'serial'));

        if ($port === '') {
            $this->error('Missing serial port. Set --port=/dev/... or WEATHER_SERIAL_PORT in .env');

            return self::FAILURE;
        }

        $this->writePidFile();
        register_shutdown_function(fn () => $this->removePidFile());

        exec(sprintf('stty -f %s %d cs8 -cstopb -parenb -icanon min 0 time 5', escapeshellarg($port), $baud));

        $handle = @fopen($port, 'r');

        if (! is_resource($handle)) {
            $this->error("Unable to open serial port: {$port}");

            return self::FAILURE;
        }

        stream_set_blocking($handle, true);
        stream_set_timeout($handle, 5);
        $this->info("Listening on {$port} at {$baud} baud...");
        $this->line('Expected JSON per line: {"temperature_c":24.2,"temperature_bmp280_c":26.7,"humidity_percent":55.1,"pressure_hpa":1012.3}');
        $this->line('Fallback text accepted: Humedad: 34 % [DHT11] Temp: 24 *C [BMP280] Temp: 26.7 *C Presion: 888.4 hPa');

        try {
            while (! feof($handle)) {
                $line = trim((string) fgets($handle));

                if ($line === '') {
                    continue;
                }

                $payload = $this->parseLine($line);

                if ($payload === null) {
                    $this->warn("Unrecognized line skipped: {$line}");

                    continue;
                }

                try {
                    $reading = $ingestor->ingest(
                        payload: $payload,
                        source: $source,
                        meta: ['raw' => $line],
                    );

                    $this->line("Saved reading #{$reading->id} at {$reading->recorded_at}");
                    $this->touchPidFile();
                } catch (\Throwable $exception) {
                    $this->error('Failed to save reading: '.$exception->getMessage());
                }
            }
        } finally {
            fclose($handle);
        }

        return self::SUCCESS;
    }

    private function pidFilePath(): string
    {
        return storage_path('app/serial-listener.pid');
    }

    private function writePidFile(): void
    {
        file_put_contents($this->pidFilePath(), json_encode([
            'pid' => getmypid(),
            'started_at' => now()->toIso8601String(),
        ]));
    }

    private function touchPidFile(): void
    {
        $path = $this->pidFilePath();
        if (file_exists($path)) {
            touch($path);
        }
    }

    private function removePidFile(): void
    {
        @unlink($this->pidFilePath());
    }

    private function parseLine(string $line): ?array
    {
        $json = json_decode($line, true);

        if (is_array($json) && isset($json['temperature_c'], $json['humidity_percent'])) {
            $json['pressure_hpa'] ??= 1013.25;

            return $json;
        }

        // Fallback: parse text like "Humedad: 34 %\t[DHT11] Temp: 24 *C\t[BMP280] Temp: 26.7 *C\tPresion: 888.4 hPa"
        // Note: the /u flag is required so [oó] matches the UTF-8 ó character.
        if (preg_match('/Humedad:\s*([\d\.]+)\s*%.*\[DHT11\]\s*Temp:\s*([\d\.]+)\s*\*C.*\[BMP280\]\s*Temp:\s*([\d\.]+)\s*\*C.*Presi[oó]n:\s*([\d\.]+)\s*hPa/u', $line, $matches)) {
            $payload = [
                'temperature_c' => (float) $matches[2],
                'temperature_bmp280_c' => (float) $matches[3],
                'humidity_percent' => (float) $matches[1],
                'pressure_hpa' => (float) $matches[4],
            ];

            if (preg_match('/Altitud\s+aprox:\s*([\d\.]+)\s*m/u', $line, $altMatch)) {
                $payload['altitude_m'] = (float) $altMatch[1];
            }

            return $payload;
        }

        // Legacy fallback: old Arduino format without BMP280
        if (preg_match('/Humedad:\s*([\d\.]+)\s*%.*Temperatura:\s*([\d\.]+)\s*\*C/', $line, $matches)) {
            return [
                'temperature_c' => (float) $matches[2],
                'humidity_percent' => (float) $matches[1],
                'pressure_hpa' => 1013.25,
            ];
        }

        return null;
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $value = AppSetting::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }
}
