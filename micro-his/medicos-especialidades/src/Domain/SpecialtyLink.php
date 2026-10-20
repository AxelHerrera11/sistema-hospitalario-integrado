<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Domain;

/** Asociación médico-especialidad (fila de doctor_specialties). */
final readonly class SpecialtyLink
{
    public function __construct(public string $specialtyId, public bool $isPrimary, public Period $period)
    {
    }
}
