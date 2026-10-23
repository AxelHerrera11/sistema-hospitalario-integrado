<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Domain;

final class IndicadorInvalidoException extends \RuntimeException
{
}

final class ErrorPersistenciaException extends \RuntimeException
{
}

/**
 * Entidad Indicador. Regla central: el valor nunca puede ser negativo.
 */
final class Indicador
{
    private function __construct(
        public readonly string $clave,
        public readonly string $etiqueta,
        public readonly int $valor
    ) {
    }

    public static function crear(string $clave, string $etiqueta, int $valor): self
    {
        if ($valor < 0) {
            throw new IndicadorInvalidoException('422 indicador_valor_invalido');
        }
        return new self($clave, $etiqueta, $valor);
    }
}
