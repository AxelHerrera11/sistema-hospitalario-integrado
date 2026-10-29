<?php

declare(strict_types=1);

foreach ([
    'src/Domain/Indicador.php',
    'src/Domain/IndicadorRepository.php',
    'src/Application/ObtenerTablero.php',
    'src/Infrastructure/IndicadorRepositoryInMemory.php',
    'src/Infrastructure/IndicadorRepositoryPDO.php',
    'src/Presentation/TableroController.php',
] as $relativo) {
    require_once __DIR__ . '/' . $relativo;
}
