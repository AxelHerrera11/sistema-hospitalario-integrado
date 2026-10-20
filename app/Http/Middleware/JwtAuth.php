<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (JWTException $exception) {
            return response()->json([
                'message' => 'Token inválido o expirado.',
            ], 401);
        }

        $tenantHeader = $request->header('X-Tenant-ID');
        $user = auth('api')->user();

        if ($tenantHeader !== null && $tenantHeader !== '' && $user !== null
            && (string) $user->tenant_id !== (string) $tenantHeader) {
            return response()->json([
                'message' => 'El tenant indicado no coincide con el usuario del token.',
            ], 403);
        }

        return $next($request);
    }
}
