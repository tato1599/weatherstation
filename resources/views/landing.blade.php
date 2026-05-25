<x-layouts.app title="Meteo-Lab | Clima" :hide-chrome="true">
    <div class="min-h-screen bg-gradient-to-b from-background via-surface to-surface-container-low">
        <div class="max-w-6xl mx-auto px-6 py-10 space-y-10">
            <header class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-extrabold text-primary">Meteo-Lab Clima Publico</h1>
                    <p class="text-on-surface-variant">Estacion local con sensores reales de temperatura, humedad y presion.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="meteo-btn-primary">Ver Panel Tecnico</a>
            </header>

            <section class="grid md:grid-cols-5 gap-6">
                <article class="meteo-panel p-6">
                    <p class="text-on-surface-variant">Temp DHT11</p>
                    <p class="text-5xl font-bold text-on-surface">{{ $latest ? number_format($latest->temperature_c, 1) : '--' }}<span class="text-2xl"> C</span></p>
                </article>
                <article class="meteo-panel p-6">
                    <p class="text-on-surface-variant">Temp BMP280</p>
                    <p class="text-5xl font-bold text-on-surface">{{ $latest && $latest->temperature_bmp280_c !== null ? number_format($latest->temperature_bmp280_c, 1) : '--' }}<span class="text-2xl"> C</span></p>
                </article>
                <article class="meteo-panel p-6">
                    <p class="text-on-surface-variant">Humedad</p>
                    <p class="text-5xl font-bold text-on-surface">{{ $latest ? number_format($latest->humidity_percent, 1) : '--' }}<span class="text-2xl"> %</span></p>
                </article>
                <article class="meteo-panel p-6">
                    <p class="text-on-surface-variant">Presion</p>
                    <p class="text-5xl font-bold text-on-surface">{{ $latest ? number_format($latest->pressure_hpa, 1) : '--' }}<span class="text-2xl"> hPa</span></p>
                </article>
                <article class="meteo-panel p-6">
                    <p class="text-on-surface-variant">Altitud</p>
                    <p class="text-5xl font-bold text-on-surface">{{ $latest && $latest->altitude_m !== null ? number_format($latest->altitude_m, 0) : '--' }}<span class="text-2xl"> m</span></p>
                </article>
            </section>

            <section class="meteo-panel p-6">
                    <h2 class="text-xl font-semibold text-on-surface">Resumen del dia</h2>
                    <p>Ultima actualizacion: {{ $latest?->recorded_at?->format('Y-m-d H:i:s') ?? 'sin datos' }}.</p>
                    <p class="text-on-surface-variant">Esta landing esta pensada para usuarios finales que solo quieren ver el estado del clima en tiempo real.</p>
            </section>
        </div>
    </div>
</x-layouts.app>
