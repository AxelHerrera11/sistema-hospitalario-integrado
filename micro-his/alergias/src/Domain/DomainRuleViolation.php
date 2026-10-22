<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Domain;

/** Regla de negocio incumplida: un código estable y el campo que la causó. */
final class DomainRuleViolation extends \DomainException
{
    public function __construct(public readonly string $rule, public readonly string $field)
    {
        parent::__construct("{$rule} ({$field})");
    }
}
