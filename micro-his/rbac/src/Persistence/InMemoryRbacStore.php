<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Persistence;

use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleResolver;

/**
 * Adaptador en memoria para desarrollo y pruebas.
 *
 * Reutiliza los contratos del motor RBAC (PermissionProvider, DenialRecorder,
 * RoleResolver) sin depender de la base de datos. NO es persistencia real: la
 * fuente definitiva de roles y permisos queda pendiente de aprobación de esquema.
 */
final class InMemoryRbacStore implements PermissionProvider, DenialRecorder, RoleResolver
{
    /** @var array<string, string[]> permisos por código de rol */
    private array $rolePermissions;

    /** @var array<string, array<string, string[]>> roles por usuario y, dentro, por hospital */
    private array $memberships = [];

    /** @var array<int, array<string, mixed>> */
    private array $denials = [];

    /**
     * @param array<string, string[]> $rolePermissions permisos asignados a cada rol
     */
    public function __construct(array $rolePermissions = [])
    {
        $this->rolePermissions = $rolePermissions;
    }

    public function grant(string $userId, string $hospitalId, string $roleCode): void
    {
        $this->memberships[$userId][$hospitalId][] = $roleCode;
    }

    public function permissionsFor(string $roleCode): array
    {
        return $this->rolePermissions[$roleCode] ?? [];
    }

    public function rolesIn(string $userId, string $hospitalId): array
    {
        return $this->memberships[$userId][$hospitalId] ?? [];
    }

    public function record(array $denial): void
    {
        $this->denials[] = $denial;
    }

    /** @return array<int, array<string, mixed>> intentos denegados registrados */
    public function denials(): array
    {
        return $this->denials;
    }
}
