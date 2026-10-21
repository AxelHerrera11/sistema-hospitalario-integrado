<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\DoctorScheduleProvider;

/** Horario semanal por configuración mientras no exista doctor_schedules (acuerdo C1). Pasarlo a PDO no toca la aplicación. */
final class ConfigScheduleProvider implements DoctorScheduleProvider
{
    /** @param array<string, array<int, list<array{0: string, 1: string}>>> $weekly médico => día ISO (1 = lunes) => franjas */
    public function __construct(private readonly array $weekly)
    {
    }

    public function scheduleFor(string $doctorId, string $date): ?array
    {
        return isset($this->weekly[$doctorId]) ? ($this->weekly[$doctorId][(int) date('N', strtotime($date))] ?? []) : null;
    }
}
