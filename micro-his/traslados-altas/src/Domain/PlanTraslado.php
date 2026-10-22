<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use DateTimeImmutable;

/**
 * RF-TA-06: cerrar la estancia de origen como [inicio, T) y después abrir la de destino [T, ∞) para el mismo episodio.
 * El orden importa: bed_stays_open_care_episode_unique admite una sola estancia abierta por episodio.
 */
final readonly class PlanTraslado
{
    public function __construct(public string $estanciaOrigenId, public string $camaOrigenId, public string $episodioId,
        public string $camaDestinoId, public string $motivo, public DateTimeImmutable $t)
    {
    }

    /** Solo se construye después de que rechazoDeEstancia y rechazoDeDestino no rechacen. */
    public static function desde(EstadoInternacion $estado, SolicitudTraslado $solicitud, DateTimeImmutable $t): self
    {
        return new self((string) $estado->estanciaAbiertaId, (string) $estado->camaActualId, $estado->episodioId,
            $solicitud->camaDestinoId, $solicitud->motivo, $t);
    }

    public function resultado(string $estanciaDestinoId): ResultadoTraslado
    {
        return new ResultadoTraslado($this->camaOrigenId, $this->estanciaOrigenId, $this->t, $estanciaDestinoId, $this->camaDestinoId, $this->episodioId);
    }
}
