<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth as JWT; // alias: evita choque con el nombre de esta clase (PHP ignora mayúsculas)

class JwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $jwt = JWT::parseToken();
            $payload = $jwt->getPayload();
        } catch (JWTException) {
            return response()->json([
                'message' => 'Token inválido o expirado.',
            ], 401);
        }

        $tenant = $request->attributes->get('tenant');

        if ((string) $payload->get('tenant_id') !== (string) $tenant->getKey()) {
            return response()->json([
                'message' => 'El tenant indicado no coincide con el usuario del token.',
            ], 403);
        }

        try {
            $user = $jwt->authenticate();
        } catch (JWTException) {
            $user = false;
        }

        if ($user === false) {
            return response()->json([
                'message' => 'Token inválido o expirado.',
            ], 401);
        }

        return $next($request);
    }
}
