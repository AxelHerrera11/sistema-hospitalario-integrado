<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Persistence;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;
use MicroHIS\Citas\Domain\ErrorPersistenciaException;

/** Doble en memoria (tests) y doble que simula fallo de persistencia. */
final class CitaRepositoryEnMemoria implements CitaRepository
{
    /** @var array<string, Cita> */
    private array $citas = [];

    public function todas(): array
    {
        return array_values($this->citas);
    }

    public function guardar(Cita $cita): void
    {
        $this->citas[$cita->id] = $cita;
    }

    public function buscar(string $id): ?Cita
    {
        return $this->citas[$id] ?? null;
    }
}

final class CitaRepositoryQueFalla implements CitaRepository
{
    public function todas(): array
    {
        throw new ErrorPersistenciaException('fallo simulado de persistencia');
    }

    public function guardar(Cita $cita): void
    {
        throw new ErrorPersistenciaException('fallo simulado de persistencia');
    }

    public function buscar(string $id): ?Cita
    {
        throw new ErrorPersistenciaException('fallo simulado de persistencia');
    }
}
