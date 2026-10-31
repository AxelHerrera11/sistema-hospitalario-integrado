<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\PermissionChecker;

/** Doble de RBAC mientras no esté integrado: permisos por actor en memoria; `$down` simula RBAC caído. */
final class InMemoryPermissionChecker implements PermissionChecker
{
    /** @param array<string, list<string>> $permissions actor => permisos */
    public function __construct(private readonly array $permissions, private readonly bool $down = false)
    {
    }

    public function allows(string $actorId, string $permission): bool
    {
        $this->down && throw new \RuntimeException('RBAC no disponible');
        return in_array($permission, $this->permissions[$actorId] ?? [], true);
    }
}
