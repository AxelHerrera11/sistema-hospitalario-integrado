<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Integration;

/**
 * Lee la matriz rol → permisos ACTIVA de config/rbac.php (clave 'roles'). Las concesiones de 'pending' no se leen,
 * igual que App\Rbac\ConfiguredPermissions: un permiso propuesto queda denegado hasta que RBAC lo active.
 */
final class RbacCatalog
{
    /** @return array<string, list<string>> */
    public static function activeRolePermissions(string $configFile): array
    {
        $config = require $configFile;

        return is_array($config['roles'] ?? null) ? $config['roles'] : [];
    }
}
