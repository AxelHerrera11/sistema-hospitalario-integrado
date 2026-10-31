<?php

declare(strict_types=1);
/*
 * Front controller CLI del Micro-HIS Reportes operativos (Presentation).
 * Uso:
 *   php bin/generar_reporte.php --desde=2026-10-01 --hasta=2026-10-31 --formato=csv
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Reportes\Application\GenerarReporte;
use MicroHIS\Reportes\Persistence\ExportadorCsv;
use MicroHIS\Reportes\Persistence\ExportadorJson;
use MicroHIS\Reportes\Persistence\ReporteRepositoryEnMemoria;

$opciones = getopt('', ['desde:', 'hasta:', 'formato:']);
$repositorio = new ReporteRepositoryEnMemoria([
    ['fecha' => '2026-10-05', 'area' => 'consultas', 'total' => 12],
    ['fecha' => '2026-10-12', 'area' => 'urgencias', 'total' => 7],
    ['fecha' => '2026-11-02', 'area' => 'consultas', 'total' => 9],
]);
$caso = new GenerarReporte($repositorio, [new ExportadorCsv(), new ExportadorJson()]);

$desde = (string) ($opciones['desde'] ?? '');
$hasta = (string) ($opciones['hasta'] ?? '');
$formato = (string) ($opciones['formato'] ?? 'csv');
if ($desde === '' || $hasta === '') {
    fwrite(STDERR, "Error: faltan --desde y --hasta\n");
    exit(2);
}

try {
    fwrite(STDOUT, $caso->ejecutar($desde, $hasta, $formato));
    fwrite(STDOUT, "OK reporte {$desde}..{$hasta} formato={$formato}\n");
    exit(0);
} catch (\RuntimeException $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(2);
}
