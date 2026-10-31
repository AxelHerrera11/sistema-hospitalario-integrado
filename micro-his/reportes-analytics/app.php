<?php

declare(strict_types=1);

use MicroHis\Reportes\Application\CalculateCancellationRate;
use MicroHis\Reportes\Application\EpisodeSummaryReader;
use MicroHis\Reportes\Persistence\InMemoryEpisodeSummaryReader;
use MicroHis\Reportes\Persistence\PdoEpisodeSummaryReader;
use MicroHis\Reportes\Presentation\IndicatorController;
use MicroHis\Reportes\Presentation\JsonView;
use MicroHis\Reportes\Presentation\Router;

require __DIR__.'/src/Presentation/Router.php';
require __DIR__.'/src/Presentation/IndicatorController.php';
require __DIR__.'/src/Presentation/JsonView.php';
require __DIR__.'/src/Application/CalculateCancellationRate.php';
require __DIR__.'/src/Application/EpisodeSummaryReader.php';
require __DIR__.'/src/Domain/IndicatorReading.php';
require __DIR__.'/src/Domain/IndicatorViolation.php';
require __DIR__.'/src/Domain/Period.php';
require __DIR__.'/src/Persistence/PdoEpisodeSummaryReader.php';
require __DIR__.'/src/Persistence/InMemoryEpisodeSummaryReader.php';

/** Raíz de composición: el mismo caso de uso funciona con cualquier
 * adaptador del puerto (InMemory en pruebas, PDO con PostgreSQL real). */
function reportes_router(EpisodeSummaryReader $reader): Router
{
    return new Router(
        new IndicatorController(new CalculateCancellationRate($reader), new JsonView()),
        new JsonView(),
    );
}

