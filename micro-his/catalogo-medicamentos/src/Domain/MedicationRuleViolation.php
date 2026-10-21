<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Domain;

final class MedicationRuleViolation extends \DomainException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
