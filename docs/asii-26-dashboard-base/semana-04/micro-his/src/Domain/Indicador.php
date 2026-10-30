<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Domain;

final class IndicadorInvalidoException extends \RuntimeException {}
final class ErrorPersistenciaException extends \RuntimeException {}

// Entidad Indicador del tablero. Un indicador expone valor y meta por periodo.
final class Indicador
{
    private function __construct(
        public readonly string $clave,
        public readonly string $periodo,
        public readonly float $valor,
        public readonly float $meta
    ) {
    }

    public static function registrar(string $clave, string $periodo, float $valor, float $meta): self
    {
        if ($clave === '' || $periodo === '') {
            throw new IndicadorInvalidoException('422 indicador_invalido');
        }
        return new self($clave, $periodo, $valor, $meta);
    }

    public function cumpleMeta(): bool
    {
        return $this->valor >= $this->meta;
    }
}
