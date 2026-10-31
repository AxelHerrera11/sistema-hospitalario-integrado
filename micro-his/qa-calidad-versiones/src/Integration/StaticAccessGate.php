<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Integration;

use MicroHis\QaCalidad\Application\AccessGate;
use MicroHis\QaCalidad\Application\AccessUnavailable;
use MicroHis\QaCalidad\Application\Actor;

/**
 * Gate estático para la demo, el CLI de CI y las pruebas, mientras los permisos
 * qa.* sigan pendientes en el catálogo RBAC. Concede por "usuario@tenant": un
 * permiso en un hospital no vale en otro. Con $unavailable simula la caída del RBAC.
 */
final class StaticAccessGate implements AccessGate
{
    /** @param array<string, list<string>> $grants "usuario@tenant" => permisos */
    public function __construct(
        private readonly array $grants,
        private readonly bool $unavailable = false,
    ) {}

    public function allows(Actor $actor, string $permission): bool
    {
        if ($this->unavailable) {
            throw new AccessUnavailable('RBAC no disponible (simulado).');
        }

        return in_array($permission, $this->grants[$actor->userId.'@'.$actor->tenantId] ?? [], true);
    }
}
