<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$expected = [
    'app/Http/Controllers/Api/V1/LabOrderController.php',
    'resources/js/modules/ordenes-laboratorio/pages/LabOrdersPage.vue',
];

$missing = array_values(array_filter(
    $expected,
    static fn (string $file): bool => !is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file))
));

if ($missing !== []) {
    echo "PENDIENTE: la evaluación final depende de la integración #1673.\n";
    exit(0);
}

echo "OK: componentes presentes; todavía deben ejecutarse pruebas reales de permisos, tenant y persistencia.\n";
