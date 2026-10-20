<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

/** Permite si algún rol del usuario en el hospital otorga el permiso requerido. */
final class RoleGrantsPermission implements AuthorizationRule
{
    public function decide(array $context): ?string
    {
        foreach ($context['roles'] as $role) {
            if (in_array($context['permission'], $context['permissions'][$role], true)) {
                return null;
            }
        }
        return 'sin_permiso_de_rol';
    }
}
