<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

/** Puerto hacia RBAC (ASII-02): ¿el actor tiene el permiso `modulo.accion`? Si RBAC no responde, lanza \RuntimeException. */
interface PermissionChecker
{
    public function allows(string $actorId, string $permission): bool;
}
