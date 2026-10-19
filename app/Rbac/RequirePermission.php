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

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Debe ejecutarse después de tenant y jwt.auth. Los IDs proceden del servidor.
        $user = $request->user('api');
        if ($user === null) {
            return $this->error(401, 'AUTH_REQUIRED');
        }
        $tenant = $request->attributes->get('tenant');
        $hospitalId = $tenant?->getKey();
        $userId = $user->getAuthIdentifier();
        if (!is_string($hospitalId) || $hospitalId === '' || !is_string($userId) || $userId === '') {
            return $this->error(503, 'RBAC_CONTEXT_UNAVAILABLE');
        }
        $decision = $this->authorize->execute($userId, $hospitalId, $permission);
        if (!$decision['allowed']) {
            $dependency = in_array($decision['reason'], ['resolucion_no_disponible', 'registro_no_disponible'], true);
            return $this->error($dependency ? 503 : 403, $dependency ? 'RBAC_UNAVAILABLE' : 'RBAC_DENIED', $decision['trace_id']);
        }
        return $next($request);
    }

    private function error(int $status, string $code, ?string $trace = null): Response
    {
        return response()->json(['code' => $code, 'trace_id' => $trace ?? 'trace-'.bin2hex(random_bytes(6))], $status);
    }
}
