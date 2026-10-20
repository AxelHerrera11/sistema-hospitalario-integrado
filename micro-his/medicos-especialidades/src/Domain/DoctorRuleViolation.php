<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Domain;

/** Regla de dominio incumplida, con el estado HTTP sugerido. */
final class DoctorRuleViolation extends \DomainException
{
    public function __construct(string $code, public readonly int $status)
    {
        parent::__construct($code);
    }
}
