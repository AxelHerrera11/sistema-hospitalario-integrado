<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

use MicroHis\Medicos\Domain\SpecialtyLink;

/** Puerto de escritura del alta (UC-MED-01). Lo usa solo RegisterDoctor. */
interface DoctorProfileStore
{
    public function licenseExists(string $license): bool;

    /** Crea el perfil, su especialidad principal y la auditoría en una sola transacción; devuelve el id. */
    public function createDoctor(string $userRef, string $license, SpecialtyLink $primary, string $actorId): string;
}
