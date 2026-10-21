<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

use MicroHis\Medicos\Domain\DoctorRuleViolation;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyAssignmentPolicy;
use MicroHis\Medicos\Domain\SpecialtyLink;

/** UC-MED-02 y 03: asociación de una especialidad adicional (RF-MED-03 a RF-MED-05). */
final class AssociateSpecialty
{
    public function __construct(private readonly DoctorSpecialtyStore $specialties, private readonly SpecialtyCatalog $catalog, private readonly SpecialtyAssignmentPolicy $policy)
    {
    }

    public function execute(string $actorId, string $doctorId, string $specialtyCode, bool $isPrimary, string $from, ?string $to = null): SpecialtyLink
    {
        $specialtyId = $this->catalog->activeSpecialtyId($specialtyCode) ?? throw new DoctorRuleViolation('SPECIALTY_INACTIVE', 422);
        [$profile, $current] = $this->specialties->specialtiesOf($doctorId) ?? throw new DoctorRuleViolation('DOCTOR_NOT_FOUND', 404);
        $link = new SpecialtyLink($specialtyId, $isPrimary, new Period($from, $to));
        $this->policy->assertCanAssociate($profile, $current, $link);
        $this->specialties->associate($doctorId, $link, $actorId);
        return $link;
    }
}
