<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

use MicroHis\Medicos\Domain\DoctorRuleViolation;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyLink;

/** UC-MED-01: alta del médico con su especialidad principal (RF-MED-01 a RF-MED-03). */
final class RegisterDoctor
{
    public function __construct(private readonly DoctorProfileStore $profiles, private readonly SpecialtyCatalog $catalog)
    {
    }

    public function execute(string $actorId, string $userRef, string $license, string $specialtyCode, string $validFrom): string
    {
        if ($userRef === '' || $license === '') {
            throw new DoctorRuleViolation('PAYLOAD_INVALID', 422);
        }
        $primary = new SpecialtyLink($this->catalog->activeSpecialtyId($specialtyCode) ?? throw new DoctorRuleViolation('SPECIALTY_INACTIVE', 422), true, new Period($validFrom));
        if ($this->profiles->licenseExists($license)) {
            throw new DoctorRuleViolation('LICENSE_ALREADY_REGISTERED', 409);
        }
        return $this->profiles->createDoctor($userRef, $license, $primary, $actorId);
    }
}
