<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/**
 * Puerto hacia el motor RBAC compartido (separa identidad de permisos). El expediente no decide roles ni permisos:
 * pregunta si el usuario, dentro del hospital (tenant), tiene el permiso. Adaptador real: Integration\RbacAccessAuthorizer.
 */
interface AccessAuthorizer
{
    public function decide(string $userId, string $hospitalId, string $permissionName): AccessDecision;
}
