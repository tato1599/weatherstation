<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Meteo-Lab' }}</title>
    <script>
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const fallbackTheme = prefersDark ? 'dark' : 'light';

        const normalizeMaryStorage = (key, fallback) => {
            const rawValue = localStorage.getItem(key);

            if (rawValue === null) {
                return fallback;
            }

            try {
                const parsedValue = JSON.parse(rawValue);

                if (typeof parsedValue === 'string' && parsedValue.length > 0) {
                    return parsedValue;
                }
            } catch {
                const plainValue = rawValue.replaceAll('"', '').trim();

                if (plainValue.length > 0) {
                    return plainValue;
                }
            }

            return fallback;
        };

        const theme = normalizeMaryStorage('mary-theme', fallbackTheme);
        const htmlClass = normalizeMaryStorage('mary-class', fallbackTheme);

        localStorage.setItem('mary-theme', JSON.stringify(theme));
        localStorage.setItem('mary-class', JSON.stringify(htmlClass));

        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('class', htmlClass);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-background text-on-surface font-body-md min-h-screen overflow-x-hidden">
    @if (!($hideChrome ?? false))
        <aside class="hidden md:flex w-[280px] h-screen fixed left-0 top-0 bg-surface-container-low border-r border-outline-variant/50 flex-col py-6 px-3 z-50 shadow-2xl">
            @php
                $listenerRunning = app(\App\Services\SerialPortManager::class)->isListenerRunning();
            @endphp
            <div class="mb-8 px-3">
                <h1 class="text-2xl font-black text-primary mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-secondary">cloud</span>Meteo-Lab</h1>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $listenerRunning ? 'bg-tertiary-fixed-dim' : 'bg-error' }}"></span>
                    <p class="font-label-md text-label-md text-on-surface-variant">Hardware {{ $listenerRunning ? 'Activo' : 'Inactivo' }}</p>
                </div>
            </div>
            <nav class="flex-1 space-y-1">
                <a href="{{ route('live') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('live') ? 'text-secondary font-bold border-r-2 border-secondary bg-surface-container-low' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                    <span class="material-symbols-outlined">sensors</span><span class="font-label-md">Tiempo Real</span>
                </a>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'text-secondary font-bold border-r-2 border-secondary bg-surface-container-low' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                    <span class="material-symbols-outlined">dashboard</span><span class="font-label-md">Monitoreo en Vivo</span>
                </a>
                <a href="{{ route('hardware') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('hardware') ? 'text-secondary font-bold border-r-2 border-secondary bg-surface-container-low' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                    <span class="material-symbols-outlined">settings_input_component</span><span class="font-label-md">Config. de Hardware</span>
                </a>
                <a href="{{ route('landing') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-low">
                    <span class="material-symbols-outlined">partly_cloudy_day</span><span class="font-label-md">Inicio</span>
                </a>
            </nav>
            <div class="mt-auto border-t border-outline-variant pt-4 px-3 space-y-3">
                <div class="flex items-center justify-between">
                    <button type="button" class="meteo-icon-btn" onclick="window.dispatchEvent(new CustomEvent('mary-toggle-theme'))" aria-label="Cambiar tema">
                        <span class="material-symbols-outlined">contrast</span>
                    </button>
                    <div id="local-clock" class="meteo-badge">--</div>
                </div>
                <a href="{{ route('dashboard') }}" class="meteo-btn-primary w-full">Abrir Panel</a>
            </div>
        </aside>
    @endif

    <main class="{{ ($hideChrome ?? false) ? '' : 'md:ml-[280px]' }} min-h-screen p-4 lg:p-8">
        {{ $slot }}
    </main>

    <x-theme-toggle lightTheme="light" darkTheme="dark" lightClass="light" darkClass="dark" class="hidden" />

    @livewireScriptConfig
    <script>
        (function() {
            const clock = document.getElementById('local-clock');
            if (!clock) return;

            function tick() {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                const h = String(now.getHours()).padStart(2, '0');
                const min = String(now.getMinutes()).padStart(2, '0');
                clock.textContent = `${y}-${m}-${d} ${h}:${min}`;
            }

            tick();
            setInterval(tick, 1000);
        })();
    </script>
</body>
</html>
