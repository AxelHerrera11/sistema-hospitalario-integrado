<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use DateTimeImmutable;

/** El alta cierra la estancia y el episodio con la misma marca temporal T. */
final readonly class ResultadoAlta extends LiberacionDeCama
{
    public function __construct(string $camaOrigenId, string $estanciaCerradaId, DateTimeImmutable $t, public string $episodioCerradoId)
    {
        parent::__construct($camaOrigenId, $estanciaCerradaId, $t);
    }
}
