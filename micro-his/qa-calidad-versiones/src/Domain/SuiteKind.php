<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** Etapa del pipeline a la que pertenece una suite planificada. */
enum SuiteKind: string
{
    case Syntax = 'syntax';
    case Lint = 'lint';
    case StaticAnalysis = 'static_analysis';
    case Build = 'build';
    case Unit = 'unit';
    case Integration = 'integration';
    case Contract = 'contract';
    case E2e = 'e2e';

    public static function parse(mixed $value): self
    {
        $kind = is_string($value) ? self::tryFrom($value) : null;
        if ($kind === null) {
            throw new InvalidQaInput('tipo_suite_invalido', 'El tipo de suite no es válido.');
        }

        return $kind;
    }
}
