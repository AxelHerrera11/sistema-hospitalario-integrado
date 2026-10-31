<?php

declare(strict_types=1);

// Autocarga del namespace MicroHis\ExpedienteMedico desde src/ (PHP vanilla, sin Composer ni framework).
// También carga los micro-HIS cuyos contratos reales se reutilizan: RBAC (Authorize), Gobernanza (RecordAuditEvent)
// y Pacientes (PatientRepository). Ninguno de ellos se modifica desde este módulo.
spl_autoload_register(static function (string $class): void {
    $prefix = 'MicroHis\\ExpedienteMedico\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require_once __DIR__ . '/../rbac/bootstrap.php';
require_once __DIR__ . '/../gobernanza-auditoria/bootstrap.php';
require_once __DIR__ . '/../pacientes/bootstrap.php';
