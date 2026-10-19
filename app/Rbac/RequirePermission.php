<?php

declare(strict_types=1);

namespace App\Rbac;

use Closure;
use Illuminate\Http\Request;
use MicroHis\Rbac\Application\Authorize;
use Symfony\Component\HttpFoundation\Response;

final class RequirePermission
{
    public function __construct(private readonly Authorize $authorize) {}

    public function handle(Request $request, Closure $next, string $permission = ''): Response
    {
        if (!preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $permission)) {
            return $this->error(503, 'RBAC_PERMISSION_NOT_CONFIGURED');
        }
        // Debe ejecutarse después de tenant y jwt.auth. Los IDs proceden del servidor.
        try {
            $user = $request->user('api');
            $tenant = $request->attributes->get('tenant');
            $hospitalId = is_object($tenant) && method_exists($tenant, 'getKey') ? $tenant->getKey() : null;
            $userId = $user?->getAuthIdentifier();
        } catch (\Throwable) {
            return $this->error(503, 'RBAC_CONTEXT_UNAVAILABLE');
        }
        if ($user === null) {
            return $this->error(401, 'AUTH_REQUIRED');
        }
        if (!is_string($hospitalId) || $hospitalId === '' || !is_string($userId) || $userId === '') {
            return $this->error(503, 'RBAC_CONTEXT_UNAVAILABLE');
        }
        $tenantHeader = $request->header('X-Tenant-ID');
        if (!is_string($tenantHeader) || $tenantHeader === '') {
            return $this->error(400, 'TENANT_REQUIRED');
        }
        if (!hash_equals($hospitalId, $tenantHeader)) {
            return $this->error(403, 'TENANT_MISMATCH');
        }
        $decision = $this->authorize->execute($userId, $hospitalId, $permission);
        if (!$decision['allowed']) {
            $dependency = in_array($decision['reason'], ['resolucion_no_disponible', 'registro_no_disponible'], true);
            return $this->error($dependency ? 503 : 403, $dependency ? 'RBAC_UNAVAILABLE' : 'RBAC_DENIED', $decision['trace_id']);
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }

    private function error(int $status, string $code, ?string $trace = null): Response
    {
        return response()->json(['code' => $code, 'trace_id' => $trace ?? 'trace-'.bin2hex(random_bytes(6))], $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
