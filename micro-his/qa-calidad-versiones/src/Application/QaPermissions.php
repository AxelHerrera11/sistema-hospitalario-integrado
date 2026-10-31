<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/**
 * PROPUESTA pendiente de validación con María (RBAC). Estos nombres NO están en
 * config/rbac.php; por eso el módulo no se conecta todavía al middleware rbac de
 * Laravel y la demo usa un AccessGate estático. Ver docs/CI-E2E/semana-05.
 */
final class QaPermissions
{
    public const VIEW = 'qa.ejecuciones.ver';

    public const REGISTER = 'qa.ejecuciones.registrar';

    public const EVALUATE = 'qa.gates.evaluar';

    private function __construct() {}
}
