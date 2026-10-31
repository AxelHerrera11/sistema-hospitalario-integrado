<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Diagnóstico asociado a una nota: código CIE-10 y tipo permitido por la migración 000400. */
final readonly class Diagnosis
{
    public const TYPES = ['primary', 'secondary', 'differential'];
    /** Forma de un código CIE-10 (J18, J18.9, S72.001A): se rechaza cualquier otra cosa antes de consultar el catálogo. */
    public const CODE_PATTERN = '/^[A-Z][0-9][0-9A-Z](\.[0-9A-Z]{1,4})?$/';

    public function __construct(public string $code, public string $type)
    {
        if (preg_match(self::CODE_PATTERN, $code) !== 1 || !in_array($type, self::TYPES, true)) {
            throw new SoapRuleViolation('DIAGNOSIS_INVALID', 422);
        }
    }
}
