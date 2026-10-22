<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

/** Alta hospitalaria: solo la estancia esperada; no lleva destino ni motivo. */
final readonly class SolicitudAlta
{
    public string $estanciaEsperadaId;

    public function __construct(string $estanciaEsperadaId)
    {
        $this->estanciaEsperadaId = Identificador::exigir($estanciaEsperadaId, 'estancia_esperada');
    }
}
