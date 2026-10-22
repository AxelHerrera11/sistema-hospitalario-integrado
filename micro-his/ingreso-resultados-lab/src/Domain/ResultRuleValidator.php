<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Domain;

final class ResultRuleValidator
{
    public static function assertValueCanBeSaved(string $value, string $unit, string $expectedUnit): void
    {
        if (trim($value) === '') {
            throw new DomainRuleViolation('El valor del resultado es obligatorio.');
        }

        if ($unit !== $expectedUnit) {
            throw new DomainRuleViolation('La unidad enviada no coincide con la unidad esperada.');
        }

        if (!is_numeric($value)) {
            throw new DomainRuleViolation('El valor del resultado debe ser numerico.');
        }
    }
}
