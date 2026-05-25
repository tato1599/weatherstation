<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WeatherSerialWatch extends Command
{
    protected $signature = 'weather:serial-watch
                            {--port= : Serial port path}
                            {--baud=9600 : Baud rate}';

    protected $description = 'Watch serial port and auto start/stop the listener when Arduino connects/disconnects';

    private ?int $listenerPid = null;

    public function handle(): int
    {
        $port = (string) ($this->option('port') ?: $this->setting('weather.serial.port', env('WEATHER_SERIAL_PORT', '')));
        $baud = (int) ($this->option('baud') ?: $this->setting('weather.serial.baud', env('WEATHER_SERIAL_BAUD', 9600)));

        if ($port === '') {
            $this->error('Missing serial port. Set --port=/dev/... or WEATHER_SERIAL_PORT in .env');

            return self::FAILURE;
        }

        $this->registerSignalHandlers();
        $this->info("Watching {$port} at {$baud} baud...");
        $this->line('Connect your Arduino to start listening. Disconnect to stop automatically.');

        while (true) {
            $portExists = file_exists($port);
            $listenerRunning = $this->listenerPid !== null && $this->isProcessRunning($this->listenerPid);

            if ($portExists && ! $listenerRunning) {
                $this->info('Port detected. Starting listener...');
                $this->listenerPid = $this->startListener($port, $baud);
            } elseif (! $portExists && $listenerRunning) {
                $this->warn('Port lost. Stopping listener...');
                $this->stopListener($this->listenerPid);
                $this->listenerPid = null;
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            sleep(3);
        }
    }

    private function startListener(string $port, int $baud): int
    {
        $logPath = escapeshellarg(storage_path('logs/serial-listener.log'));
        $phpBinary = escapeshellarg(PHP_BINARY);
        $escapedPort = escapeshellarg($port);
        $escapedBaud = escapeshellarg((string) $baud);

        $cmd = "({$phpBinary} artisan weather:serial-listen --port={$escapedPort} --baud={$escapedBaud} > {$logPath} 2>&1) & echo \$!";

        $pid = (int) trim((string) shell_exec($cmd));

        if ($pid <= 0) {
            $this->error('Failed to start listener process.');
        }

        return $pid;
    }

    private function stopListener(int $pid): void
    {
        if ($this->isProcessRunning($pid)) {
            posix_kill($pid, SIGTERM);
            usleep(500000);

            if ($this->isProcessRunning($pid)) {
                posix_kill($pid, SIGKILL);
            }
        }
    }

    private function isProcessRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        return posix_kill($pid, 0);
    }

    private function registerSignalHandlers(): void
    {
        if (! function_exists('pcntl_signal')) {
            return;
        }

        pcntl_signal(SIGINT, function () {
            $this->shutdown('Interrupted (Ctrl+C).');
        });

        pcntl_signal(SIGTERM, function () {
            $this->shutdown('Terminated.');
        });
    }

    private function shutdown(string $reason): void
    {
        $this->newLine();
        $this->warn($reason);

        if ($this->listenerPid !== null) {
            $this->stopListener($this->listenerPid);
            $this->info('Listener stopped.');
        }

        exit(self::SUCCESS);
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $value = \App\Models\AppSetting::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }
}
