<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

/** Catálogo de especialidades: devuelve el id solo si la especialidad está activa. */
interface SpecialtyCatalog
{
    public function activeSpecialtyId(string $code): ?string;
}
