<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\QaStorageFailure;

/** Exige un permiso antes de cada caso de uso y deja rastro de cada denegación. */
final class Authorizer
{
    public function __construct(
        private readonly AccessGate $gate,
        private readonly AuditTrail $audit,
    ) {}

    /**
     * @throws AccessDenied sin permiso (403)
     * @throws AccessUnavailable el RBAC no pudo decidir (503)
     */
    public function require(Actor $actor, string $permission, string $targetId, string $traceId): void
    {
        if ($this->gate->allows($actor, $permission)) {
            return;
        }
        try {
            $this->audit->record($actor, 'qa.acceso.denegado', 'denied', $targetId, $traceId, 'permiso_faltante', ['permiso' => $permission]);
        } catch (QaStorageFailure) {
            // La denegación se mantiene aunque la auditoría falle: nunca se abre el acceso por un error.
        }

        throw new AccessDenied($permission);
    }
}
