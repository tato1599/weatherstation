<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class SerialPortManager
{
    public function listPorts(): array
    {
        $patterns = ['/dev/tty.*', '/dev/cu.*'];
        $ports = [];

        foreach ($patterns as $pattern) {
            $matches = glob($pattern);

            if (is_array($matches)) {
                $ports = array_merge($ports, $matches);
            }
        }

        $ports = array_values(array_unique($ports));
        sort($ports);

        return $ports;
    }

    public function isListenerRunning(): bool
    {
        $pidFile = storage_path('app/serial-listener.pid');

        if (! file_exists($pidFile)) {
            return false;
        }

        $content = file_get_contents($pidFile);
        $data = json_decode($content, true);

        if (! is_array($data) || ! isset($data['pid'])) {
            @unlink($pidFile);

            return false;
        }

        $pid = (int) $data['pid'];

        if (function_exists('posix_kill') && ! posix_kill($pid, 0)) {
            @unlink($pidFile);

            return false;
        }

        // Fallback: if the file was not touched in the last 60 seconds, consider it stale.
        if (time() - filemtime($pidFile) > 60) {
            @unlink($pidFile);

            return false;
        }

        return true;
    }

    public function isPortInUse(string $port): bool
    {
        return ! empty($this->getPortProcesses($port));
    }

    /**
     * @return array<int, array{name: string, pid: int, command: string}>
     */
    public function getPortProcesses(string $port): array
    {
        if (! file_exists($port)) {
            return [];
        }

        $result = Process::run('lsof '.escapeshellarg($port));

        if (! $result->successful()) {
            return [];
        }

        $lines = explode("\n", trim($result->output()));
        $processes = [];

        // Skip header line
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            $parts = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);

            if (count($parts) >= 9) {
                $command = implode(' ', array_slice($parts, 8));
                $processes[] = [
                    'name' => $parts[0],
                    'pid' => (int) $parts[1],
                    'command' => $command,
                ];
            }
        }

        return $processes;
    }

    public function killListener(): void
    {
        Process::run('pkill -f '.escapeshellarg('[w]eather:serial-listen'));
    }

    public function killPortProcesses(string $port): void
    {
        $result = Process::run('lsof -t '.escapeshellarg($port));

        if ($result->successful() && trim($result->output()) !== '') {
            $pids = array_filter(explode("\n", trim($result->output())));
            foreach ($pids as $pid) {
                if (is_numeric($pid)) {
                    Process::run('kill -9 '.(int) $pid);
                }
            }
        }
    }

    /**
     * Read a recognizable line from the serial port within the timeout.
     * Skips blank lines and unrecognized text (e.g. Arduino boot messages).
     */
    public function readLine(string $port, int $baud, int $timeoutSeconds = 8): ?array
    {
        if (! file_exists($port)) {
            return null;
        }

        exec(sprintf('stty -f %s %d cs8 -cstopb -parenb -icanon min 0 time 5', escapeshellarg($port), $baud));

        $handle = @fopen($port, 'r');

        if (! is_resource($handle)) {
            return null;
        }

        stream_set_blocking($handle, true);
        stream_set_timeout($handle, max(1, (int) ($timeoutSeconds / 2)));

        $start = time();
        while (time() - $start < $timeoutSeconds) {
            $line = trim((string) fgets($handle));

            if ($line === '') {
                continue;
            }

            $payload = $this->parseLine($line);

            if ($payload !== null) {
                fclose($handle);

                return $payload;
            }

            // Unrecognized line — keep reading until timeout.
        }

        fclose($handle);

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function parseLine(string $line): ?array
    {
        $json = json_decode($line, true);

        if (is_array($json) && isset($json['temperature_c'], $json['humidity_percent'])) {
            $json['pressure_hpa'] ??= 1013.25;

            return $json;
        }

        // BMP280 format with UTF-8 ó
        if (preg_match('/Humedad:\s*([\d\.]+)\s*%.*\[DHT11\]\s*Temp:\s*([\d\.]+)\s*\*C.*\[BMP280\]\s*Temp:\s*([\d\.]+)\s*\*C.*Presi[oó]n:\s*([\d\.]+)\s*hPa/u', $line, $matches)) {
            $payload = [
                'temperature_c' => (float) $matches[2],
                'temperature_bmp280_c' => (float) $matches[3],
                'humidity_percent' => (float) $matches[1],
                'pressure_hpa' => (float) $matches[4],
                'raw' => $line,
            ];

            // Optional altitude from BMP280
            if (preg_match('/Altitud\s+aprox:\s*([\d\.]+)\s*m/u', $line, $altMatch)) {
                $payload['altitude_m'] = (float) $altMatch[1];
            }

            return $payload;
        }

        // Legacy format without BMP280
        if (preg_match('/Humedad:\s*([\d\.]+)\s*%.*Temperatura:\s*([\d\.]+)\s*\*C/', $line, $matches)) {
            return [
                'temperature_c' => (float) $matches[2],
                'humidity_percent' => (float) $matches[1],
                'pressure_hpa' => 1013.25,
                'raw' => $line,
            ];
        }

        return null;
    }
}
