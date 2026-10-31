<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Tenants\Infrastructure\Persistence\Hospital;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureHospitalDatabaseTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $hospital = $request->attributes->get('tenant');
        $hospitalId = $hospital instanceof Hospital ? (string) $hospital->getKey() : null;
        $databaseTenantId = config('tenants.hospital_database_tenant_id');

        if (! is_string($databaseTenantId) || trim($databaseTenantId) === '') {
            return response()->json([
                'error' => 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED',
                'message' => 'La base hospitalaria no está asignada a un hospital.',
            ], 503);
        }

        if ($hospitalId === null || ! hash_equals(trim($databaseTenantId), $hospitalId)) {
            return response()->json([
                'error' => 'HOSPITAL_DATABASE_TENANT_MISMATCH',
                'message' => 'El hospital no está disponible en esta instancia.',
            ], 403);
        }

        return $next($request);
    }
}
