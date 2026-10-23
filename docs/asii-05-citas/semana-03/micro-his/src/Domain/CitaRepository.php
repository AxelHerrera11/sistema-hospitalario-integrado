<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Domain;

/**
 * Puerto de persistencia de citas. Definido en el Dominio (Inversion de Dependencias).
 */
interface CitaRepository
{
    /** @return Cita[] */
    public function todas(): array;

    public function guardar(Cita $cita): void;

    public function buscar(string $id): ?Cita;
}
