<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\AppointmentReader;

/** Doble del módulo Citas: horas ocupadas fijas, sin datos del paciente. */
final class InMemoryAppointmentReader implements AppointmentReader
{
    /** @param array<string, array<string, list<string>>> $busy médico => fecha => horas HH:MM ocupadas */
    public function __construct(private readonly array $busy = [])
    {
    }

    public function busySlots(string $doctorId, string $date): array
    {
        return $this->busy[$doctorId][$date] ?? [];
    }
}
