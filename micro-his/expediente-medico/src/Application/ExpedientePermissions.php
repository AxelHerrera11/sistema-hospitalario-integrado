<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Application;

/**
 * Permisos `modulo.accion` que exige el flujo. CONSULTA existe en el catálogo real (config/rbac.php) y está activo
 * para superadmin, medico y enfermeria. APERTURA_PROPUESTA (expediente.abrir) se activó el 30/10/2026 para medico y
 * superadmin; conserva el nombre de la constante para no romper a los consumidores.
 */
final class ExpedientePermissions
{
    public const CONSULTA = 'expediente.ver';
    public const APERTURA_PROPUESTA = 'expediente.abrir';
}
