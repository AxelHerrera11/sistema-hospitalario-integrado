<?php

declare(strict_types=1);
/* DESPUES: Value Object Horario, metodo con intencion (bloquea) y validacion separada de la regla. */

namespace Refactor\Citas;

final class Horario
{
    public function __construct(public readonly string $desde, public readonly string $hasta)
    {
        if ($desde >= $hasta) {
            throw new \DomainException('cita_rango_invalido');
        }
    }

    public function seSolapaCon(self $otro): bool
    {
        return $this->desde < $otro->hasta && $otro->desde < $this->hasta;
    }
}

final class CitaAgendada
{
    private const ACTIVOS = ['scheduled', 'confirmed'];

    public function __construct(
        public readonly string $paciente,
        public readonly string $doctor,
        public readonly Horario $horario,
        public readonly string $estado = 'scheduled'
    ) {
    }

    public function bloquea(string $paciente, string $doctor, Horario $horario): bool
    {
        $mismoActor = $this->paciente === $paciente || $this->doctor === $doctor;
        return in_array($this->estado, self::ACTIVOS, true) && $mismoActor && $this->horario->seSolapaCon($horario);
    }
}

final class Agenda
{
    /** @var list<CitaAgendada> */
    public array $citas = [];

    public function agendar(string $paciente, string $doctor, Horario $horario): void
    {
        foreach ($this->citas as $cita) {
            if ($cita->bloquea($paciente, $doctor, $horario)) {
                throw new \DomainException('appointments_overlap');
            }
        }
        $this->citas[] = new CitaAgendada($paciente, $doctor, $horario);
    }
}

function agendarCitaDespues(Agenda $agenda, array $datos): array
{
    if (array_diff(['paciente', 'doctor', 'desde', 'hasta'], array_keys($datos)) !== []) {
        return ['status' => 422, 'error' => 'validation_failed'];
    }
    try {
        $agenda->agendar($datos['paciente'], $datos['doctor'], new Horario($datos['desde'], $datos['hasta']));
    } catch (\DomainException $e) {
        return ['status' => 409, 'error' => $e->getMessage()];
    }
    return ['status' => 201, 'error' => null];
}
