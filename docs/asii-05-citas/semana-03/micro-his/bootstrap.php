<?php

declare(strict_types=1);
/*
 * Micro-HIS Citas (ASII-05, Semana 3) - bootstrap sin Composer.
 */

foreach ([
    __DIR__ . '/src/Domain/Cita.php',
    __DIR__ . '/src/Domain/CitaRepository.php',
    __DIR__ . '/src/Application/AgendarCita.php',
    __DIR__ . '/src/Persistence/CitaRepositoryPDO.php',
    __DIR__ . '/src/Persistence/CitaRepositoryEnMemoria.php',
] as $archivo) {
    require_once $archivo;
}
