<?php

declare(strict_types=1);
/*
 * Front controller CLI del Micro-HIS Dashboard base (Presentation).
 * Uso:
 *   php bin/resumen_dashboard.php --sembrar
 */

require __DIR__ . '/../bootstrap.php';

use MicroHIS\Dashboard\Application\ResumenDashboard;
use MicroHIS\Dashboard\Domain\Indicador;
use MicroHIS\Dashboard\Persistence\IndicadorRepositoryEnMemoria;

$opciones = getopt('', ['sembrar']);
$repositorio = new IndicadorRepositoryEnMemoria();

if (isset($opciones['sembrar'])) {
    $repositorio->agregar(Indicador::crear('citas_hoy', 'Citas de hoy', 12));
    $repositorio->agregar(Indicador::crear('pacientes_activos', 'Pacientes activos', 42));
    $repositorio->agregar(Indicador::crear('docs_pendientes', 'Documentos pendientes', 3));
}

$resumen = (new ResumenDashboard($repositorio))->ejecutar();
if ($resumen === []) {
    fwrite(STDOUT, "Sin indicadores (use --sembrar)\n");
    exit(0);
}

foreach ($resumen as $indicador) {
    fwrite(STDOUT, "OK {$indicador['clave']} = {$indicador['valor']} ({$indicador['etiqueta']})\n");
}
exit(0);
