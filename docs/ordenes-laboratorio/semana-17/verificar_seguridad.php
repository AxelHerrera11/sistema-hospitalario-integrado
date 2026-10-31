<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$required = [
    'app/Http/Controllers/Api/V1/LabOrderController.php',
    'database/migrations/hospital/2026_10_31_000001_create_lab_orders_tables.php',
];

$missing = array_values(array_filter(
    $required,
    static fn (string $file): bool => !is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file))
));

if ($missing !== []) {
    echo "PENDIENTE: integrar primero la Semana 16 (#1673).\n";
    foreach ($missing as $file) {
        echo "- falta: {$file}\n";
    }
    exit(0);
}

echo "OK: base de la Semana 16 disponible; ejecutar pruebas de tenant, permisos y transacción.\n";
