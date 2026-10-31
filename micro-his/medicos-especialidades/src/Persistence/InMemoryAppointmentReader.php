<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\AppointmentReader;

/** Doble del módulo Citas: intervalos ocupados fijos, sin datos del paciente. */
final class InMemoryAppointmentReader implements AppointmentReader
{
    /** @param array<string, array<string, list<array{0: string, 1: string}>>> $busy médico => fecha => intervalos [inicio, fin) HH:MM ocupados */
    public function __construct(private readonly array $busy = [])
    {
    }

    public function busyIntervals(string $doctorId, string $date): array
    {
        return $this->busy[$doctorId][$date] ?? [];
    }

    public function busyIntervalsFor(array $doctorIds, string $date): array
    {
        return array_combine($doctorIds, array_map(fn (string $id): array => $this->busyIntervals($id, $date), $doctorIds));
    }
}
