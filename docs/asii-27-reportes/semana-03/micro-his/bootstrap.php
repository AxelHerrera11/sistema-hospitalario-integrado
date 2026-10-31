<?php

declare(strict_types=1);
/*
 * Micro-HIS Reportes operativos (ASII-27, Semana 3) - bootstrap sin Composer.
 */

foreach ([
    __DIR__ . '/src/Domain/Reporte.php',
    __DIR__ . '/src/Domain/ReporteRepository.php',
    __DIR__ . '/src/Domain/Exportador.php',
    __DIR__ . '/src/Application/GenerarReporte.php',
    __DIR__ . '/src/Persistence/ReporteRepositoryEnMemoria.php',
    __DIR__ . '/src/Persistence/ExportadorCsv.php',
    __DIR__ . '/src/Persistence/ExportadorJson.php',
] as $archivo) {
    require_once $archivo;
}
