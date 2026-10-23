<?php

declare(strict_types=1);
/*
 * Micro-HIS Dashboard base (ASII-26, Semana 3) - bootstrap sin Composer.
 */

foreach ([
    __DIR__ . '/src/Domain/Indicador.php',
    __DIR__ . '/src/Domain/IndicadorRepository.php',
    __DIR__ . '/src/Application/ResumenDashboard.php',
    __DIR__ . '/src/Persistence/IndicadorRepositoryEnMemoria.php',
] as $archivo) {
    require_once $archivo;
}
