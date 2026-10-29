<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Infrastructure;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;

/** Adaptador Repository en memoria (pruebas y demos sin base de datos). */
final class CitaRepositoryInMemory implements CitaRepository
{
    private array $citas = [];

    public function todas(): array
    {
        return array_values($this->citas);
    }

    public function guardar(Cita $cita): void
    {
        $this->citas[$cita->id] = $cita;
    }
}
