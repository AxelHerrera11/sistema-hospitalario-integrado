<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$required = [
    'database/migrations/hospital',
    'tests/Database/Hospital',
    'docs/verificacion-postgresql/estado-integracion-esquema.md',
    'docs/verificacion-postgresql/semana-15/README.md',
];

$missing = [];
foreach ($required as $relative) {
    if (!file_exists($root . DIRECTORY_SEPARATOR . $relative)) {
        $missing[] = $relative;
    }
}

$postgresTests = glob($root . '/tests/Database/Hospital/*PostgresTest.php') ?: [];
if ($postgresTests === []) {
    $missing[] = 'tests/Database/Hospital/*PostgresTest.php';
}

if ($missing !== []) {
    fwrite(STDERR, "FALLA: faltan evidencias requeridas:\n");
    foreach ($missing as $item) {
        fwrite(STDERR, "- {$item}\n");
    }
    exit(1);
}

echo 'OK: estructura de verificación PostgreSQL disponible (' . count($postgresTests) . " pruebas encontradas)\n";
echo "Nota: este control no sustituye la ejecución contra PostgreSQL.\n";
