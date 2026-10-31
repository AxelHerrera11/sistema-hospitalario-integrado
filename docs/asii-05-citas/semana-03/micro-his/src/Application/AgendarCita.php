<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Application;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;
use MicroHIS\Citas\Domain\SolapeCitaException;

final class EntradaCita
{
    public function __construct(
        public readonly string $paciente,
        public readonly string $doctor,
        public readonly string $desde,
        public readonly string $hasta
    ) {
    }
}

final class ResultadoCita
{
    public function __construct(public readonly string $id, public readonly string $estado)
    {
    }
}

/**
 * Caso de uso: agendar una cita validando anti-solape antes de persistir.
 */
final class AgendarCita
{
    public function __construct(private readonly CitaRepository $repositorio)
    {
    }

    public function ejecutar(EntradaCita $entrada): ResultadoCita
    {
        $cita = Cita::crear(
            'C-' . bin2hex(random_bytes(4)),
            $entrada->paciente,
            $entrada->doctor,
            $entrada->desde,
            $entrada->hasta
        );

        foreach ($this->repositorio->todas() as $existente) {
            if ($existente->estado() === Cita::CANCELLED) {
                continue;
            }
            if ($existente->seSolapaCon($entrada->paciente, $entrada->doctor, $entrada->desde, $entrada->hasta)) {
                throw new SolapeCitaException('409 appointments_overlap');
            }
        }

        $this->repositorio->guardar($cita);

        return new ResultadoCita($cita->id, $cita->estado());
    }
}
