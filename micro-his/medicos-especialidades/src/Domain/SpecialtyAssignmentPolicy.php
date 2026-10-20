<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Domain;

/** Reglas de RF-MED-04 y RF-MED-05. Sin dependencias: se prueba sin base de datos. */
final class SpecialtyAssignmentPolicy
{
    /** @param list<SpecialtyLink> $current */
    public function assertCanAssociate(Period $profile, array $current, SpecialtyLink $new): void
    {
        if (!$profile->contains($new->period)) {
            throw new DoctorRuleViolation('PERIOD_OUTSIDE_PROFILE', 422);
        }
        foreach ($current as $link) {
            if (!$link->period->overlaps($new->period)) {
                continue;
            }
            if ($link->specialtyId === $new->specialtyId) {
                throw new DoctorRuleViolation('SPECIALTY_ALREADY_ASSOCIATED', 409);
            }
            if ($link->isPrimary && $new->isPrimary) {
                throw new DoctorRuleViolation('PRIMARY_SPECIALTY_CONFLICT', 409);
            }
        }
    }
}
