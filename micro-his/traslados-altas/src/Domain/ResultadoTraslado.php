<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use DateTimeImmutable;

/** El traslado abre la estancia destino [T, ∞) y deja abierto el mismo episodio. */
final readonly class ResultadoTraslado extends LiberacionDeCama
{
    public function __construct(string $camaOrigenId, string $estanciaCerradaId, DateTimeImmutable $t,
        public string $estanciaDestinoId, public string $camaDestinoId, public string $episodioAbiertoId)
    {
        parent::__construct($camaOrigenId, $estanciaCerradaId, $t);
    }
}
