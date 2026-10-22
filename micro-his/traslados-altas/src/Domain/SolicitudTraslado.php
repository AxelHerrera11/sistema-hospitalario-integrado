<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

/** Traslado interno: la estancia que el usuario cree vigente, la cama destino y el motivo (propios del traslado). */
final readonly class SolicitudTraslado
{
    public string $estanciaEsperadaId;
    public string $camaDestinoId;
    public string $motivo;

    public function __construct(string $estanciaEsperadaId, string $camaDestinoId, string $motivo)
    {
        $this->estanciaEsperadaId = Identificador::exigir($estanciaEsperadaId, 'estancia_esperada');
        $this->camaDestinoId = Identificador::exigir($camaDestinoId, 'cama_destino');
        $motivo = trim($motivo);
        // bed_stays.reason exige texto no vacío; 500 es el límite propuesto para la interfaz.
        if ($motivo === '' || mb_strlen($motivo) > 500) {
            throw new SolicitudInvalida('motivo');
        }
        $this->motivo = $motivo;
    }
}
