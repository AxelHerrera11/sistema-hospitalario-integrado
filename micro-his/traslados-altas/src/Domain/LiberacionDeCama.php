<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use DateTimeImmutable;

/** Parte común de todo éxito (RF-TA-09): qué cama se liberó, qué estancia se cerró y en qué T. */
readonly class LiberacionDeCama
{
    public function __construct(public string $camaOrigenId, public string $estanciaCerradaId, public DateTimeImmutable $t)
    {
    }
}
