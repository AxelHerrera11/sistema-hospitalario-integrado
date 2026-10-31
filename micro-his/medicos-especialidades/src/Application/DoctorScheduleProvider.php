<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

/** Horario del médico. Sin tabla en el esquema federado (acuerdo C1): se implementa por configuración. */
interface DoctorScheduleProvider
{
    /** @return list<array{0: string, 1: string, 2?: int}>|null franjas [inicio, fin, minutos por bloque opcional] del día, o null si no hay horario */
    public function scheduleFor(string $doctorId, string $date): ?array;
}
