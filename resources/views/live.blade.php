<x-layouts.app title="Meteo-Lab | Tiempo Real">
    <div class="max-w-7xl mx-auto space-y-6 pb-20 md:pb-0">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-primary">Monitoreo en Tiempo Real</h1>
            <p class="text-on-surface-variant">Lectura directa desde el puerto serial sin guardar en base de datos.</p>
        </header>

        <div id="status-banner" class="hidden mb-4 rounded-xl p-4 border">
            <p id="status-text" class="text-sm font-semibold"></p>
            <p id="status-hint" class="text-xs mt-1"></p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <article class="meteo-panel-soft p-4">
                <p class="text-sm text-on-surface-variant">Temp DHT11</p>
                <div id="val-dht" class="text-4xl font-bold text-on-surface">--</div>
                <span class="text-sm text-on-surface-variant">C</span>
            </article>
            <article class="meteo-panel-soft p-4">
                <p class="text-sm text-on-surface-variant">Temp BMP280</p>
                <div id="val-bmp" class="text-4xl font-bold text-on-surface">--</div>
                <span class="text-sm text-on-surface-variant">C</span>
            </article>
            <article class="meteo-panel-soft p-4">
                <p class="text-sm text-on-surface-variant">Humedad</p>
                <div id="val-hum" class="text-4xl font-bold text-on-surface">--</div>
                <span class="text-sm text-on-surface-variant">%</span>
            </article>
            <article class="meteo-panel-soft p-4">
                <p class="text-sm text-on-surface-variant">Presion</p>
                <div id="val-pres" class="text-4xl font-bold text-on-surface">--</div>
                <span class="text-sm text-on-surface-variant">hPa</span>
            </article>
            <article class="meteo-panel-soft p-4">
                <p class="text-sm text-on-surface-variant">Altitud BMP280</p>
                <div id="val-alt" class="text-4xl font-bold text-on-surface">--</div>
                <span class="text-sm text-on-surface-variant">m</span>
            </article>
        </div>

        <div class="flex items-center gap-4">
            <button id="btn-toggle" class="meteo-btn-primary px-8">Conectar</button>
            <span id="conn-badge" class="meteo-badge bg-gray-500">Desconectado</span>
        </div>

        <div class="meteo-panel-soft p-4 mt-6">
            <p class="text-sm text-on-surface-variant font-semibold">Linea cruda (raw)</p>
            <pre id="val-raw" class="text-xs text-on-surface-variant mt-1 whitespace-pre-wrap break-all">--</pre>
        </div>

        <div class="meteo-panel-soft p-4 mt-4">
            <p class="text-sm text-on-surface-variant">Notas:</p>
            <ul class="text-xs text-on-surface-variant list-disc list-inside mt-1 space-y-1">
                <li>Este modo lee directamente del puerto serial sin guardar nada en la base de datos.</li>
                <li>El puerto serial solo puede ser usado por un programa a la vez. Si tienes el <strong>listener de base de datos</strong> activo, detenlo primero en <a href="{{ route('hardware') }}" class="text-secondary underline">Config. de Hardware</a>.</li>
                <li>Si el Monitor Serie de Arduino IDE esta abierto, cierralo o usa el boton "Reiniciar / Liberar Puerto" en Hardware.</li>
            </ul>
        </div>
    </div>

    <script>
        (function() {
            const btn = document.getElementById('btn-toggle');
            const badge = document.getElementById('conn-badge');
            const banner = document.getElementById('status-banner');
            const statusText = document.getElementById('status-text');
            const statusHint = document.getElementById('status-hint');
            const els = {
                dht: document.getElementById('val-dht'),
                bmp: document.getElementById('val-bmp'),
                hum: document.getElementById('val-hum'),
                pres: document.getElementById('val-pres'),
                alt: document.getElementById('val-alt'),
                raw: document.getElementById('val-raw'),
            };

            let running = false;
            let timer = null;
            let fetching = false;

            function showStatus(type, text, hint) {
                banner.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'bg-yellow-50', 'border-yellow-200', 'bg-green-50', 'border-green-200');
                statusText.classList.remove('text-red-700', 'text-yellow-700', 'text-green-700');

                if (type === 'error') {
                    banner.classList.add('bg-red-50', 'border-red-200');
                    statusText.classList.add('text-red-700');
                } else if (type === 'warn') {
                    banner.classList.add('bg-yellow-50', 'border-yellow-200');
                    statusText.classList.add('text-yellow-700');
                } else {
                    banner.classList.add('bg-green-50', 'border-green-200');
                    statusText.classList.add('text-green-700');
                }

                statusText.textContent = text;
                statusHint.textContent = hint || '';
            }

            function hideStatus() {
                banner.classList.add('hidden');
            }

            function setConnected(isConnected) {
                if (isConnected) {
                    badge.textContent = 'Conectado';
                    badge.className = 'meteo-badge bg-green-600';
                    btn.textContent = 'Desconectar';
                } else {
                    badge.textContent = 'Desconectado';
                    badge.className = 'meteo-badge bg-gray-500';
                    btn.textContent = 'Conectar';
                }
            }

            async function tick() {
                if (!running || fetching) return;
                fetching = true;

                try {
                    const res = await fetch('{{ route('api.serial.live') }}');
                    const json = await res.json();

                    if (json.ok && json.data) {
                        hideStatus();
                        els.dht.textContent = json.data.temperature_c != null ? json.data.temperature_c.toFixed(1) : '--';
                        els.bmp.textContent = json.data.temperature_bmp280_c != null ? json.data.temperature_bmp280_c.toFixed(1) : '--';
                        els.hum.textContent = json.data.humidity_percent != null ? json.data.humidity_percent.toFixed(1) : '--';
                        els.pres.textContent = json.data.pressure_hpa != null ? json.data.pressure_hpa.toFixed(1) : '--';
                        els.alt.textContent = json.data.altitude_m != null ? json.data.altitude_m.toFixed(0) : '--';
                        els.raw.textContent = json.data.raw || '--';
                    } else {
                        showStatus('error', json.error || 'Error desconocido', json.hint || '');
                        if (json.processes) {
                            const procs = json.processes.map(p => `${p.name} (PID ${p.pid})`).join(', ');
                            statusHint.textContent = (json.hint || '') + ' Procesos: ' + procs;
                        }
                    }
                } catch (e) {
                    showStatus('error', 'No se pudo contactar con el servidor.', 'Verifica que la app este corriendo.');
                } finally {
                    fetching = false;
                }
            }

            btn.addEventListener('click', () => {
                running = !running;
                setConnected(running);

                if (running) {
                    hideStatus();
                    tick();
                    timer = setInterval(tick, 5500); // Arduino sends every 5s; 5.5s avoids missing cycles
                } else {
                    clearInterval(timer);
                    timer = null;
                    hideStatus();
                }
            });
        })();
    </script>
</x-layouts.app>
