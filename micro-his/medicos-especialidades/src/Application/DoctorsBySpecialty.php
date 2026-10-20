<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

/** Lectura: médicos activos con la especialidad vigente en una fecha. */
interface DoctorsBySpecialty
{
    /** @return list<string> */
    public function doctorsWith(string $specialtyId, string $date): array;
}
