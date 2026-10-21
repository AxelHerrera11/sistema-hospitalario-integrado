<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Application;

/** Lo que el caso de uso necesita saber del episodio: si está abierto y la edad del paciente para elegir el perfil. */
final readonly class EpisodeContext
{
    public function __construct(public bool $open, public int $patientAge)
    {
    }
}
