<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\PermissionChecker;

/** Doble de RBAC mientras no exista el servicio real. Con $down simula RBAC caído. */
final class InMemoryPermissionChecker implements PermissionChecker
{
    /** @param array<string, list<string>> $grants actor => permisos `modulo.accion` */
    public function __construct(private readonly array $grants = [], private readonly bool $down = false)
    {
    }

    public function allows(string $actorId, string $permission): bool
    {
        $this->down && throw new \RuntimeException('rbac_no_disponible');
        return in_array($permission, $this->grants[$actorId] ?? [], true);
    }
}
