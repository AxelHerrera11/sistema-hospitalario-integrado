<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Application;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;
use MicroHIS\Citas\Domain\SolapeCitaException;

/** Caso de uso: agendar una cita. Contiene la regla de anti-solape; no conoce HTTP ni SQL. */
final class AgendarCita
{
    public function __construct(private readonly CitaRepository $repositorio)
    {
    }

    public function ejecutar(string $paciente, string $doctor, string $desde, string $hasta): Cita
    {
        $cita = Cita::agendar('C-' . bin2hex(random_bytes(4)), $paciente, $doctor, $desde, $hasta);
        foreach ($this->repositorio->todas() as $existente) {
            if ($existente->seSolapaCon($cita->paciente, $cita->doctor, $cita->desde, $cita->hasta)) {
                throw new SolapeCitaException('409 appointments_overlap');
            }
        }
        $this->repositorio->guardar($cita);
        return $cita;
    }
}
