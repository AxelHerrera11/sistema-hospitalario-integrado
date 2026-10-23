<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Domain;

/** Violación de una regla de la prescripción; la capa de presentación la traduce a 422 con su campo. */
final class PrescriptionRuleViolation extends \DomainException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
