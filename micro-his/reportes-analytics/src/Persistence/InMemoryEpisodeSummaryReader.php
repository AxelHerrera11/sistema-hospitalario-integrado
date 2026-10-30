<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Persistence;

use MicroHis\Reportes\Application\EpisodeSummaryReader;

/** Doble de lectura para pruebas y demo; misma coherencia que el adaptador real. */
final class InMemoryEpisodeSummaryReader implements EpisodeSummaryReader
{
    public int $finalized = 0;
    public int $cancelled = 0;
    public bool $fail = false;
    public bool $returnNull = false;
    /** @var array<int> Cuando no es null, se devuelve sin coherencia. */
    public ?array $forced = null;

    public function read(string $fromInclusive, string $untilExclusive, string $serviceOrEmpty): ?array
    {
        if ($this->fail) {
            throw new \RuntimeException('lectura_no_disponible');
        }
        if ($this->returnNull) {
            return null;
        }
        if ($this->forced !== null) {
            return ['finalized' => $this->forced[0], 'cancelled' => $this->forced[1]];
        }

        return $this->finalized === 0 ? null : ['finalized' => $this->finalized, 'cancelled' => $this->cancelled];
    }
}
