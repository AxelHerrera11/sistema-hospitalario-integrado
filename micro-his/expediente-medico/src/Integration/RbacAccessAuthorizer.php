<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Integration;

use MicroHis\ExpedienteMedico\Application\AccessAuthorizer;
use MicroHis\ExpedienteMedico\Application\AccessDecision;
use MicroHis\Rbac\Application\Authorize;

/**
 * Adaptador al motor RBAC real (micro-his/rbac, María). No reimplementa reglas: delega en Authorize, que ya
 * resuelve roles por hospital, falla segura y trace_id. El expediente solo traduce su contrato.
 */
final class RbacAccessAuthorizer implements AccessAuthorizer
{
    public function __construct(private readonly Authorize $authorize)
    {
    }

    public function decide(string $userId, string $hospitalId, string $permissionName): AccessDecision
    {
        $result = $this->authorize->execute($userId, $hospitalId, $permissionName);

        return new AccessDecision($result['allowed'], $result['reason'], $result['trace_id']);
    }
}
