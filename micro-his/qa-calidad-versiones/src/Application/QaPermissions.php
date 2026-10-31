<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/**
 * Activos en config/rbac.php desde el 30/10/2026 para superadmin y admin_hospital.
 * En Laravel los exige el middleware rbac y RbacAccessGate; la demo y el CLI de CI
 * siguen con StaticAccessGate. Ver docs/CI-E2E/semana-16/integracion-laravel.md.
 */
final class QaPermissions
{
    public const VIEW = 'qa.ejecuciones.ver';

    public const REGISTER = 'qa.ejecuciones.registrar';

    public const EVALUATE = 'qa.gates.evaluar';

    private function __construct() {}
}
