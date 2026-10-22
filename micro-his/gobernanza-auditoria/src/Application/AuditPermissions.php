<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

/**
 * Permiso RBAC (formato modulo.accion) requerido para consultar la bitácora.
 *
 * PROPUESTO, no activo: "auditoria.ver" no existe todavía en config/rbac.php
 * (ni en 'roles' ni en 'catalog'). Mientras RBAC no lo apruebe, toda consulta
 * se deniega con 403. Ver docs/gobernanza-auditoria/semana-05/contrato-api.md.
 */
final class AuditPermissions
{
    public const VIEW = 'auditoria.ver';
}
