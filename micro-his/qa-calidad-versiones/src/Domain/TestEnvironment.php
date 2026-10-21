<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * Ambiente donde corre la ejecución. No existe un caso "producción": las pruebas
 * E2E solo se ejecutan contra ambientes aislados con datos ficticios (RNF-02).
 */
enum TestEnvironment: string
{
    case Local = 'local';
    case Ci = 'ci';
    case Staging = 'staging';

    public static function parse(mixed $value): self
    {
        $environment = is_string($value) ? self::tryFrom($value) : null;
        if ($environment === null) {
            throw new InvalidQaInput('ambiente_no_permitido', 'Solo se aceptan los ambientes local, ci o staging.');
        }

        return $environment;
    }
}
