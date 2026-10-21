<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Regla de dominio incumplida, con el estado HTTP sugerido. */
final class SoapRuleViolation extends \DomainException
{
    public function __construct(string $code, public readonly int $status)
    {
        parent::__construct($code);
    }
}
