<?php

declare(strict_types=1);

foreach ([
    'src/Domain/Cita.php',
    'src/Domain/CitaRepository.php',
    'src/Application/AgendarCita.php',
    'src/Infrastructure/CitaRepositoryInMemory.php',
    'src/Infrastructure/CitaRepositoryPDO.php',
    'src/Presentation/CitaController.php',
] as $relativo) {
    require_once __DIR__ . '/' . $relativo;
}
