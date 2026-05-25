# Visor de Clima con Laravel

Proyecto Laravel para visualizar datos de temperatura, humedad y presion atmosferica desde Arduino.

## Incluye

- Dashboard web con:
  - ultima lectura
  - grafica de ultimas 24 horas
  - historicos paginados
- Ingestion por API REST protegida con token
- Ingestion por Serial Bus con comando Artisan

## Requisitos

- PHP y Composer (versiones instaladas en tu sistema)
- PostgreSQL

## Configuracion rapida

1. Edita `.env` con tu base de datos PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clima_visor
DB_USERNAME=postgres
DB_PASSWORD=
```

2. Configura token y serial:

```env
WEATHER_INGEST_TOKEN=tu-token-seguro
WEATHER_SERIAL_PORT=/dev/tty.usbmodem14101
WEATHER_SERIAL_BAUD=9600
```

3. Ejecuta migraciones:

```bash
php artisan migrate
```

4. Levanta el proyecto:

```bash
composer run dev
```

## API de ingreso de datos

Endpoint:

`POST /api/v1/readings`

Header obligatorio:

`X-Weather-Token: <WEATHER_INGEST_TOKEN>`

Body JSON:

```json
{
  "temperature_c": 24.7,
  "humidity_percent": 58.4,
  "pressure_hpa": 1011.8,
  "recorded_at": "2026-05-18T16:30:00Z"
}
```

`recorded_at` es opcional.

## Serial Bus

Comando:

```bash
php artisan weather:serial-listen --port=/dev/tty.usbmodem14101 --baud=9600
```

Formato esperado por linea (JSON):

```json
{"temperature_c":24.2,"humidity_percent":55.1,"pressure_hpa":1012.3}
```

## Frecuencia recomendada

Se configura para trabajar bien con muestreo cada **30 segundos**. Si cambias la frecuencia, el sistema sigue funcionando igual.
