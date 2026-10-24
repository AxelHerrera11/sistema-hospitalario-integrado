<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Application;

/** Puerto del caso de uso: una lectura agregada con los conteos del periodo.
 * No devuelve detalles clinicos ni datos personales. */
interface EpisodeSummaryReader
{
    /** @return array{finalized: int, cancelled: int}|null Los conteos del periodo, o null si no hay filas. */
    public function read(string $fromInclusive, string $untilExclusive, string $serviceOrEmpty): ?array;
}
