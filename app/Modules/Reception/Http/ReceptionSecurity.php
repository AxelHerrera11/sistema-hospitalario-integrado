<?php

namespace App\Modules\Reception\Http;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ReceptionSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlation = (string) Str::uuid();
        $key = 'reception:'.hash('sha256', (string) $request->ip());
        $limit = (int) config('reception.requests_per_minute', 60);
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $response = response()->json(['error'=>'RATE_LIMITED','message'=>'Demasiadas solicitudes.'],429,
                ['Retry-After'=>(string) RateLimiter::availableIn($key)]);
        } elseif (strlen($request->getContent()) > 65536) {
            $response = response()->json(['error'=>'PAYLOAD_TOO_LARGE','message'=>'Solicitud demasiado grande.'],413);
        } else {
            RateLimiter::hit($key, 60);
            try { $response = $next($request); }
            catch (\Throwable $error) { $response = app(ExceptionHandler::class)->render($request, $error); }
        }
        if ($response->getStatusCode() >= 500) $response = response()->json([
            'error'=>'RECEPTION_UNAVAILABLE','message'=>'Servicio de recepción no disponible. Reintente con la misma clave.'
        ],503);
        $response->headers->set('Cache-Control','no-store, private, max-age=0');
        $response->headers->set('Pragma','no-cache');
        $response->headers->set('X-Content-Type-Options','nosniff');
        $response->headers->set('Referrer-Policy','no-referrer');
        $response->headers->set('X-Correlation-ID',$correlation);
        // No registrar cuerpo, query, código de muestra, tokens, contraseñas ni nombres de pacientes.
        Log::info('reception.request', ['correlation_id'=>$correlation,'method'=>$request->method(),
            'route'=>$request->route()?->uri(),'status'=>$response->getStatusCode(),
            'actor_id'=>$request->user()?->getAuthIdentifier(),
            'hospital_id'=>$request->attributes->get('tenant')?->getKey()]);
        return $response;
    }
}
