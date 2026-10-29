<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Domain;

/** Interfaz Repository en el limite del Dominio: el Dominio depende de ella y la infraestructura la implementa. */
interface CitaRepository
{
    /** @return list<Cita> */
    public function todas(): array;

    public function guardar(Cita $cita): void;
}
