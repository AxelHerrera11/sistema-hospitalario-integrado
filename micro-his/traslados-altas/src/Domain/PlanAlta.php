<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Domain;

use DateTimeImmutable;

/** RF-TA-07: cerrar la estancia y luego el episodio en el mismo T, sin dejar estancias abiertas del episodio. */
final readonly class PlanAlta
{
    public function __construct(public string $estanciaId, public string $camaOrigenId, public string $episodioId, public DateTimeImmutable $t)
    {
    }

    /** Solo se construye después de que rechazoDeEstancia no rechace. */
    public static function desde(EstadoInternacion $estado, DateTimeImmutable $t): self
    {
        return new self((string) $estado->estanciaAbiertaId, (string) $estado->camaActualId, $estado->episodioId, $t);
    }

    public function resultado(): ResultadoAlta
    {
        return new ResultadoAlta($this->camaOrigenId, $this->estanciaId, $this->t, $this->episodioId);
    }
}
