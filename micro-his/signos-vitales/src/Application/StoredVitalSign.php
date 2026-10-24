<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

use MicroHis\SignosVitales\Domain\VitalSigns;

/** Medición ya guardada, tal como la devuelve el Repository: igual con PDO o InMemory. */
final readonly class StoredVitalSign
{
    public function __construct(public string $id, public string $episodeId, public string $recordedBy, public string $measuredAt, public VitalSigns $vitals)
    {
    }
}
