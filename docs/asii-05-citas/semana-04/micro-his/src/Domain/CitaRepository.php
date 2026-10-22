<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Domain;

/**
 * Interfaz Repository en el limite del Dominio (patron Repository).
 * El Dominio depende de esta abstraccion; la infraestructura la implementa.
 */
interface CitaRepository
{
    /** @return list<Cita> */
    public function todas(): array;

    public function guardar(Cita $cita): void;
}
