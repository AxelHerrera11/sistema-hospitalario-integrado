<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Application;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;
use MicroHIS\Citas\Domain\SolapeCitaException;

/**
 * Caso de uso: agendar una cita. Contiene la regla de anti-solape; no conoce HTTP ni SQL.
 */
final class AgendarCita
{
    public function __construct(private readonly CitaRepository $repositorio)
    {
    }

    public function ejecutar(string $paciente, string $doctor, string $desde, string $hasta): Cita
    {
        foreach ($this->repositorio->todas() as $existente) {
            if ($existente->seSolapaCon($paciente, $doctor, $desde, $hasta)) {
                throw new SolapeCitaException('409 appointments_overlap');
            }
        }
        $cita = Cita::agendar('C-' . bin2hex(random_bytes(4)), $paciente, $doctor, $desde, $hasta);
        $this->repositorio->guardar($cita);
        return $cita;
    }
}
