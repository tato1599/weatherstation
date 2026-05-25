<x-layouts.app title="Meteo-Lab | Configuracion de Hardware">
    <div class="max-w-6xl mx-auto space-y-6 pb-20 md:pb-0">
        <header>
            <h1 class="text-3xl font-bold text-primary">Configuracion de Hardware</h1>
            <p class="text-on-surface-variant">Gestiona puerto serial, velocidad y parametros de adquisicion.</p>
        </header>

        @if (session('status'))
            <div class="meteo-alert-success"><span>{{ session('status') }}</span></div>
        @endif

        @if ($serialStatus['configuredPort'] !== '')
            <section class="meteo-panel p-6">
                <h2 class="text-xl font-semibold text-on-surface mb-4">Estado del Puerto Serial</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="meteo-panel-soft p-4">
                        <p class="text-sm text-on-surface-variant">Puerto configurado</p>
                        <p class="text-lg font-bold text-on-surface">{{ $serialStatus['configuredPort'] }}</p>
                    </div>
                    <div class="meteo-panel-soft p-4">
                        <p class="text-sm text-on-surface-variant">Listener de la app</p>
                        <p class="text-lg font-bold {{ $serialStatus['listenerRunning'] ? 'text-green-600' : 'text-on-surface' }}">
                            {{ $serialStatus['listenerRunning'] ? 'Corriendo' : 'Detenido' }}
                        </p>
                    </div>
                    <div class="meteo-panel-soft p-4">
                        <p class="text-sm text-on-surface-variant">Puerto en uso</p>
                        <p class="text-lg font-bold {{ $serialStatus['portInUse'] ? 'text-red-500' : 'text-green-600' }}">
                            {{ $serialStatus['portInUse'] ? 'Ocupado' : 'Libre' }}
                        </p>
                    </div>
                </div>

                @if (! empty($serialStatus['portProcesses']))
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                        <p class="text-sm font-semibold text-red-700 mb-2">Procesos ocupando el puerto:</p>
                        <ul class="text-sm text-red-700 space-y-1">
                            @foreach ($serialStatus['portProcesses'] as $proc)
                                <li>{{ $proc['name'] }} (PID: {{ $proc['pid'] }}) — {{ $proc['command'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('settings.serial.listener.restart') }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="meteo-btn-primary w-full bg-red-600 hover:bg-red-700 border-red-600">
                        Reiniciar / Liberar Puerto y Reconectar
                    </button>
                </form>
                <p class="text-xs text-on-surface-variant mt-2">
                    Esto detendra todos los procesos que usen el puerto (incluido el Monitor Serie de Arduino) y reiniciara la escucha de la app.
                </p>
            </section>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <section class="md:col-span-6 meteo-panel p-6">
                    <h2 class="text-xl font-semibold text-on-surface mb-4">Conexion Serial</h2>
                    <form action="{{ route('settings.serial.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <label>
                            <span class="meteo-label">Puerto Serial</span>
                            <select name="serial_port" class="meteo-input" required>
                                @forelse($serialPorts as $port)
                                    <option value="{{ $port }}" @selected(old('serial_port', $serialConfig['port']) === $port)>{{ $port }}</option>
                                @empty
                                    <option value="{{ old('serial_port', $serialConfig['port']) }}">{{ old('serial_port', $serialConfig['port'] ?: 'No detectado') }}</option>
                                @endforelse
                            </select>
                        </label>
                        <label>
                            <span class="meteo-label">Velocidad (Baud)</span>
                            <input name="serial_baud" type="number" min="1200" max="115200" value="{{ old('serial_baud', $serialConfig['baud']) }}" class="meteo-input" required>
                        </label>
                        <label>
                            <span class="meteo-label">Etiqueta de origen</span>
                            <input name="serial_source" type="text" value="{{ old('serial_source', $serialConfig['source']) }}" class="meteo-input" required>
                        </label>

                        <button class="meteo-btn-primary w-full">Guardar configuracion</button>
                    </form>
            </section>

            <section class="md:col-span-6 meteo-panel p-6">
                    <h2 class="text-xl font-semibold text-on-surface mb-4">Registro de Datos</h2>
                    <p class="text-on-surface-variant">Frecuencia recomendada: 30 segundos.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 my-4">
                        <form action="{{ route('settings.serial.listener.start') }}" method="POST">
                            @csrf
                            <button type="submit" class="meteo-btn-primary w-full">Iniciar escucha serial</button>
                        </form>
                        <form action="{{ route('settings.serial.listener.stop') }}" method="POST">
                            @csrf
                            <button type="submit" class="meteo-btn-primary w-full">Detener escucha</button>
                        </form>
                    </div>
                    <div class="meteo-panel-soft p-4 my-4">
                        <p class="text-sm text-on-surface-variant font-semibold">Modo automatico (recomendado)</p>
                        <p class="text-sm text-on-surface-variant">Arranca y detiene solo cuando conectas/desconectas el Arduino.</p>
                        <div class="meteo-code mt-2 overflow-x-auto">
                            <pre class="whitespace-nowrap"><code>php artisan weather:serial-watch</code></pre>
                        </div>
                    </div>
                    <div class="meteo-code space-y-2 overflow-x-auto">
                        <pre class="whitespace-nowrap"><code>php artisan weather:serial-listen</code></pre>
                        <pre class="whitespace-nowrap"><code>php artisan weather:serial-listen --port=/dev/tty.usbmodem1101 --baud=9600</code></pre>
                    </div>
            </section>
        </div>
    </div>
</x-layouts.app>
