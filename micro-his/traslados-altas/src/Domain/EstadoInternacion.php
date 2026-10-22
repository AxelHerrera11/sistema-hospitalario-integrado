<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use DateTimeImmutable;

/**
 * Lo que HOSPITAL dice del episodio al que pertenece la estancia esperada, leído dentro de la transacción:
 * care_episodes y, si existe, su única estancia abierta en bed_stays (índice bed_stays_open_care_episode_unique).
 */
final readonly class EstadoInternacion
{
    public function __construct(
        public string $episodioId,
        public string $tipoEpisodio,
        public string $estadoEpisodio,
        public DateTimeImmutable $inicioEpisodio,
        public ?string $estanciaAbiertaId,
        public ?string $camaActualId,
        public ?DateTimeImmutable $inicioEstancia,
    ) {
    }
}
