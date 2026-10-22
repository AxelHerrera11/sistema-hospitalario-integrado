<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\VitalSigns;

/**
 * Repository de mediciones. Vive en Application (límite del caso de uso): los adaptadores de Persistence lo implementan
 * y el dominio y la presentación no saben si hay PDO o memoria detrás.
 */
interface VitalSignRepository
{
    /**
     * Guarda la medición y su auditoría (alerta o sin evaluación) de forma atómica.
     *
     * @param Assessment|null $assessment null cuando no hay perfil de umbrales aplicable
     * @throws \RuntimeException si la persistencia no está disponible; no queda nada guardado
     */
    public function record(string $episodeId, string $userId, VitalSigns $vitals, string $measuredAt, ?Assessment $assessment): string;

    public function find(string $vitalSignId): ?StoredVitalSign;
}
