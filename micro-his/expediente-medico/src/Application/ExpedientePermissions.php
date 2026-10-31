<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/**
 * Permisos `modulo.accion` que exige el flujo. CONSULTA existe en el catálogo real (config/rbac.php) y está activo
 * para superadmin, medico y enfermeria. APERTURA_PROPUESTA NO existe en el catálogo: es la propuesta enviada a RBAC
 * (María). Mientras no se active, el motor RBAC real la deniega a todos los roles y la apertura responde 403.
 */
final class ExpedientePermissions
{
    public const CONSULTA = 'expediente.ver';
    public const APERTURA_PROPUESTA = 'expediente.abrir';
}
