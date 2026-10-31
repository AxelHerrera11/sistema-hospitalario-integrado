<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Domain;

/** Longitudes máximas de los textos que entran por la API: un campo desmesurado es 422 antes de tocar la base. */
final class Limits
{
    public const LICENSE = 64;
    public const USER_REF = 64;
    public const SPECIALTY_CODE = 32;
    public const DOCTOR_ID = 64;

    public static function within(string $value, int $max): void
    {
        mb_strlen($value) <= $max || throw new DoctorRuleViolation('PAYLOAD_INVALID', 422);
    }
}
