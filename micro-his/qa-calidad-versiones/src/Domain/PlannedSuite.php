<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** Suite que el plan exige (required) o solo informa (opcional) antes de ejecutar. */
final readonly class PlannedSuite
{
    public function __construct(
        public string $name,
        public SuiteKind $kind,
        public bool $required,
    ) {
        if (preg_match('/^[a-z0-9][a-z0-9._-]{1,62}$/', $name) !== 1) {
            throw new InvalidQaInput('nombre_suite_invalido', 'El nombre de la suite debe tener 2-63 caracteres [a-z0-9._-].');
        }
    }
}
