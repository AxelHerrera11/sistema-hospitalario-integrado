<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Integration;

use MicroHis\QaCalidad\Application\AccessGate;
use MicroHis\QaCalidad\Application\AccessUnavailable;
use MicroHis\QaCalidad\Application\Actor;
use MicroHis\Rbac\Application\Authorize;

/**
 * AccessGate sobre el caso de uso Authorize de micro-his/rbac, con la matriz activa de config/rbac.php.
 * Si el RBAC no puede decidir (excepción o resolución/registro no disponible) se falla cerrado con 503.
 */
final class RbacAccessGate implements AccessGate
{
    private const UNAVAILABLE = ['resolucion_no_disponible', 'registro_no_disponible'];

    public function __construct(private readonly Authorize $authorize) {}

    public function allows(Actor $actor, string $permission): bool
    {
        try {
            $decision = $this->authorize->execute($actor->userId, $actor->tenantId, $permission);
        } catch (\Throwable) {
            throw new AccessUnavailable('RBAC no disponible.');
        }
        if (($decision['allowed'] ?? false) === true) {
            return true;
        }
        if (in_array($decision['reason'] ?? null, self::UNAVAILABLE, true)) {
            throw new AccessUnavailable('RBAC no disponible.');
        }

        return false;
    }
}
