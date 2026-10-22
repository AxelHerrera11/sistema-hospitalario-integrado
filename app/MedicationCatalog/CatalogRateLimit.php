<?php

declare(strict_types=1);

namespace App\MedicationCatalog;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Amenaza M12: límite de solicitudes por usuario y hospital. No usa ThrottleRequests porque Laravel lo reordena por
 * prioridad antes de tenant y jwt.auth; este middleware se ejecuta donde se declara, con la identidad ya resuelta.
 */
final class CatalogRateLimit
{
    public const REQUESTS_PER_MINUTE = 60;

    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->attributes->get('tenant');
        $hospital = is_object($tenant) && method_exists($tenant, 'getKey') ? (string) $tenant->getKey() : '';
        $key = 'medicamentos|' . sha1(($request->user('api')?->getAuthIdentifier() ?? $request->ip()) . '|' . $hospital);
        if ($this->limiter->tooManyAttempts($key, self::REQUESTS_PER_MINUTE)) {
            return response()->json(['error' => 'CATALOG_RATE_LIMITED', 'message' => 'Demasiadas solicitudes al catálogo; espere un momento.'], 429)
                ->header('Retry-After', (string) $this->limiter->availableIn($key))->header('Cache-Control', 'no-store, private');
        }
        $this->limiter->hit($key, 60);

        return $next($request);
    }
}
