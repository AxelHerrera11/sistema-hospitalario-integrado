<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Integration;

use MicroHis\Gobernanza\Application\AccessDecision;
use MicroHis\Gobernanza\Application\AccessGate;
use MicroHis\Rbac\Application\Authorize;

/**
 * AccessGate sobre el caso de uso Authorize de micro-his/rbac (develop).
 * Cualquier respuesta inesperada o excepción se traduce en denegación.
 */
final class RbacAccessGate implements AccessGate
{
    public function __construct(private readonly Authorize $authorize)
    {
    }

    public function decide(string $userId, string $tenantId, string $permission): AccessDecision
    {
        try {
            $decision = $this->authorize->execute($userId, $tenantId, $permission);
        } catch (\Throwable) {
            return new AccessDecision(false, 'resolucion_no_disponible', null);
        }

        return new AccessDecision(
            ($decision['allowed'] ?? false) === true,
            is_string($decision['reason'] ?? null) ? $decision['reason'] : null,
            is_string($decision['trace_id'] ?? null) ? $decision['trace_id'] : null,
        );
    }
}
