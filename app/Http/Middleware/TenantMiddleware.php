<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $hospitalId = $request->header('X-Tenant-ID');

        if ($hospitalId === null || $hospitalId === '') {
            return response()->json([
                'error' => 'TENANT_REQUIRED',
                'message' => 'La cabecera X-Tenant-ID es obligatoria.',
            ], 400);
        }

        if (! Str::isUuid($hospitalId)) {
            return response()->json([
                'error' => 'TENANT_INVALID',
                'message' => 'El hospital indicado no es válido.',
            ], 400);
        }

        $hospital = Hospital::query()->find($hospitalId);

        if ($hospital === null) {
            return response()->json([
                'error' => 'HOSPITAL_NOT_FOUND',
                'message' => 'Hospital no encontrado.',
            ], 404);
        }

        if ($hospital->status !== 'active') {
            return response()->json([
                'error' => 'HOSPITAL_INACTIVE',
                'message' => 'El hospital no está activo.',
            ], 403);
        }

        $request->attributes->set('tenant', $hospital);
        app()->instance('currentTenant', $hospital);

        return $next($request);
    }
}
