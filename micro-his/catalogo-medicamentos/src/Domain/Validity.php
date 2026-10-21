<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Domain;

/** Vigencia del registro de catálogo; no es el vencimiento de un lote. */
enum Validity: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public static function fromInput(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw new MedicationRuleViolation('validity', 'La vigencia solo admite active o inactive.');
    }
}
