<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\VitalSigns;

/** Puerto de persistencia. Guarda la medición y su auditoría (alerta o sin evaluación) en una sola transacción. */
interface VitalSignRepository
{
    /** @param Assessment|null $assessment null cuando no hay perfil de umbrales aplicable */
    public function record(string $episodeId, string $userId, VitalSigns $vitals, string $measuredAt, ?Assessment $assessment): string;
}
