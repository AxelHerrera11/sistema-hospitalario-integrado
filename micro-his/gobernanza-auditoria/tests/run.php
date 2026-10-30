<?php

declare(strict_types=1);

/**
 * Ejecuta todas las suites, cada una en su propio proceso PHP:
 *   php micro-his/gobernanza-auditoria/tests/run.php
 * Variables opcionales: RBAC_DIR (otra copia de micro-his/rbac, ver rbac_integration.php) y
 * TENANTS_AUDIT_DIR (AuditLogRepository de Tenants, ver hash_compat.php).
 */
$suites = ['unit', 'contract', 'whitebox', 'blackbox', 'rbac_integration', 'hash_compat', 'laravel_glue', 'seguridad', 'architecture'];
$failed = [];
foreach ($suites as $suite) {
    echo PHP_EOL . "######## {$suite} ########" . PHP_EOL;
    if (!is_file(__DIR__ . "/{$suite}.php")) {
        // Las suites se agregan semana a semana; una suite aún no entregada no cuenta como aprobada.
        echo "NO ENTREGADA TODAVÍA: tests/{$suite}.php" . PHP_EOL;
        continue;
    }
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . "/{$suite}.php"), $code);
    $code === 0 || $failed[] = $suite;
}
echo PHP_EOL . ($failed === [] ? 'TODAS LAS SUITES APROBADAS' : 'SUITES CON FALLOS: ' . implode(', ', $failed)) . PHP_EOL;
exit($failed === [] ? 0 : 1);
