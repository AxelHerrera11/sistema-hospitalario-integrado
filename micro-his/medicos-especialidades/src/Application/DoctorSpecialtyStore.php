<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyLink;

/** Puerto de las asociaciones de un médico (UC-MED-02). Lo usa solo AssociateSpecialty. */
interface DoctorSpecialtyStore
{
    /** @return array{0: Period, 1: list<SpecialtyLink>}|null vigencia del perfil activo y sus vínculos activos */
    public function specialtiesOf(string $doctorId): ?array;

    public function associate(string $doctorId, SpecialtyLink $link, string $actorId): void;
}
