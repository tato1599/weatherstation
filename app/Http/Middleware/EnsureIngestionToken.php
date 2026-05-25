<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIngestionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.weather_ingest.token');
        $providedToken = (string) $request->header('X-Weather-Token', '');

        if ($configuredToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized ingestion token.',
            ], 401);
        }

        return $next($request);
    }
}
