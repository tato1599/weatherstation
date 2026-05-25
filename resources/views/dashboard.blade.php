<x-layouts.app title="Meteo-Lab | Monitoreo en Vivo">
    <div class="max-w-7xl mx-auto space-y-6 pb-20 md:pb-0">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-primary">Condiciones Actuales</h1>
            <p class="text-on-surface-variant">Lecturas en vivo desde Arduino, actualizadas por API y serial.</p>
        </header>

        @if ($serialStatus['configuredPort'] !== '' && ($serialStatus['portInUse'] || ! $serialStatus['listenerRunning']))
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="text-sm font-semibold text-red-700">Problema detectado con el puerto serial</p>
                        <p class="text-sm text-red-600">
                            @if ($serialStatus['portInUse'] && ! $serialStatus['listenerRunning'])
                                El puerto {{ $serialStatus['configuredPort'] }} esta ocupado por otro proceso.
                            @elseif (! $serialStatus['listenerRunning'])
                                El listener de la app no esta corriendo.
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('hardware') }}" class="meteo-btn-primary bg-red-600 hover:bg-red-700 border-red-600 text-sm">Ir a Hardware para reiniciar</a>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

            <section
                class="md:col-span-8 meteo-panel p-6 relative overflow-hidden bg-gradient-to-br from-surface-container to-surface-container-high shadow-xl">
                <div class="absolute inset-0 bg-gradient-to-br from-secondary-container/20 to-transparent"></div>
                <div class="relative z-10 flex flex-col sm:flex-row justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3 text-secondary">
                            <span class="material-symbols-outlined"
                                style="font-variation-settings: 'FILL' 1">partly_cloudy_day</span>
                            <span class="font-semibold">Sistema Meteo-Lab</span>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span
                                class="text-6xl font-extrabold text-on-surface">{{ $latest ? number_format($latest->temperature_c, 1) : '--' }}</span>
                            <span class="text-xl text-on-surface-variant">C</span>
                        </div>
                    </div>
                    <div
                        class="bg-surface/60 p-3 rounded-xl border border-outline-variant/40 min-w-[220px] backdrop-blur-md">
                        <div class="flex justify-between"><span>Humedad</span><span
                                class="font-data-mono">{{ $latest ? number_format($latest->humidity_percent, 1) . ' %' : '--' }}</span>
                        </div>
                        <div class="meteo-divider my-2"></div>
                        <div class="flex justify-between"><span>Presion</span><span
                                class="font-data-mono">{{ $latest ? number_format($latest->pressure_hpa, 1) . ' hPa' : '--' }}</span>
                        </div>
                        <div class="meteo-divider my-2"></div>
                        <div class="flex justify-between"><span>Temp BMP280</span><span
                                class="font-data-mono">{{ $latest && $latest->temperature_bmp280_c !== null ? number_format($latest->temperature_bmp280_c, 1) . ' *C' : '--' }}</span>
                        </div>
                        <div class="meteo-divider my-2"></div>
                        <div class="flex justify-between"><span>Altitud</span><span
                                class="font-data-mono">{{ $latest && $latest->altitude_m !== null ? number_format($latest->altitude_m, 0) . ' m' : '--' }}</span>
                        </div>
                        <div class="meteo-divider my-2"></div>
                        <div class="flex justify-between"><span>Origen</span><span
                                class="meteo-badge">{{ $latest->source ?? 'n/a' }}</span></div>
                    </div>
                </div>
            </section>

            <section class="md:col-span-4 grid grid-cols-2 gap-4">
                <article class="meteo-panel-soft p-4">
                    <p class="text-sm text-on-surface-variant">Temp DHT11</p>
                    <div class="text-3xl font-bold text-on-surface">
                        {{ $latest ? number_format($latest->temperature_c, 1) : '--' }} C</div>
                </article>
                <article class="meteo-panel-soft p-4">
                    <p class="text-sm text-on-surface-variant">Temp BMP280</p>
                    <div class="text-3xl font-bold text-on-surface">
                        {{ $latest && $latest->temperature_bmp280_c !== null ? number_format($latest->temperature_bmp280_c, 1) : '--' }} C</div>
                </article>
                <article class="meteo-panel-soft p-4">
                    <p class="text-sm text-on-surface-variant">Humedad</p>
                    <div class="text-3xl font-bold text-on-surface">
                        {{ $latest ? number_format($latest->humidity_percent, 1) : '--' }} %</div>
                </article>
                <article class="meteo-panel-soft p-4">
                    <p class="text-sm text-on-surface-variant">Presion</p>
                    <div class="text-3xl font-bold text-on-surface">
                        {{ $latest ? number_format($latest->pressure_hpa, 1) : '--' }} hPa</div>
                </article>
                <article class="meteo-panel-soft p-4 md:col-span-2">
                    <p class="text-sm text-on-surface-variant">Altitud BMP280</p>
                    <div class="text-3xl font-bold text-on-surface">
                        {{ $latest && $latest->altitude_m !== null ? number_format($latest->altitude_m, 0) : '--' }} m</div>
                </article>
            </section>

            <section class="md:col-span-12 meteo-panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Historico Reciente</h2>
                    <span class="text-sm text-on-surface-variant">Ultimas 25 lecturas</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="meteo-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Temp DHT11</th>
                                <th>Temp BMP280</th>
                                <th>Humedad</th>
                                <th>Presion</th>
                                <th>Altitud</th>
                                <th>Origen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $row)
                                <tr>
                                    <td>{{ $row->recorded_at->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ number_format($row->temperature_c, 2) }} C</td>
                                    <td>{{ $row->temperature_bmp280_c !== null ? number_format($row->temperature_bmp280_c, 2) . ' C' : '--' }}</td>
                                    <td>{{ number_format($row->humidity_percent, 2) }} %</td>
                                    <td>{{ number_format($row->pressure_hpa, 2) }} hPa</td>
                                    <td>{{ $row->altitude_m !== null ? number_format($row->altitude_m, 0) . ' m' : '--' }}</td>
                                    <td><span class="meteo-badge">{{ $row->source }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-on-surface-variant">Sin datos todavia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $history->links() }}</div>
            </section>
        </div>
    </div>
</x-layouts.app>