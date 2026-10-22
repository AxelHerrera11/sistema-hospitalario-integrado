<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Diagnóstico asociado a una nota: código CIE-10 y tipo permitido por la migración 000400. */
final readonly class Diagnosis
{
    public const TYPES = ['primary', 'secondary', 'differential'];

    public function __construct(public string $code, public string $type)
    {
        if (trim($code) === '' || !in_array($type, self::TYPES, true)) {
            throw new SoapRuleViolation('DIAGNOSIS_INVALID', 422);
        }
    }
}
