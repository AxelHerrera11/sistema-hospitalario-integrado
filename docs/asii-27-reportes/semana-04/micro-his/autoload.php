<?php

declare(strict_types=1);

foreach ([
    'src/Domain/Reporte.php',
    'src/Domain/ReporteRepository.php',
    'src/Application/GenerarReporte.php',
    'src/Infrastructure/ReporteRepositoryInMemory.php',
    'src/Infrastructure/ReporteRepositoryPDO.php',
    'src/Presentation/ReporteController.php',
] as $relativo) {
    require_once __DIR__ . '/' . $relativo;
}
