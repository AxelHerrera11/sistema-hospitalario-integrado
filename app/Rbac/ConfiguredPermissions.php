<?php

declare(strict_types=1);

namespace App\Rbac;

use MicroHis\Rbac\Domain\PermissionProvider;

final class ConfiguredPermissions implements PermissionProvider
{
    public function permissionsFor(string $roleCode): array
    {
        // Configuración del servidor, nunca roles/permisos enviados por el cliente.
        $roles = config('rbac.roles', []);
        return $roles[$roleCode] ?? [];
    }
}
