<?php

declare(strict_types=1);

namespace MicroHis\DashboardBase\Domain;

use InvalidArgumentException;

/**
 * Indicador del tablero. Corrige los hallazgos RTF-01 y RTF-02 de la semana 13: declara si mejora al subir
 * o al bajar (`sentido`) y vuelve a rechazar valores negativos (RF-02 de la semana 3).
 */
final readonly class Indicador
{
    public const MAYOR_MEJOR = 'mayor_mejor';
    public const MENOR_MEJOR = 'menor_mejor';

    public function __construct(
        public string $clave,
        public string $titulo,
        public float $valor,
        public float $meta,
        public string $sentido,
    ) {
        if ($valor < 0 || $meta < 0) {
            throw new InvalidArgumentException("Indicador {$clave}: valor y meta no pueden ser negativos.");
        }
        if (!in_array($sentido, [self::MAYOR_MEJOR, self::MENOR_MEJOR], true)) {
            throw new InvalidArgumentException("Indicador {$clave}: sentido desconocido.");
        }
    }

    public function cumpleMeta(): bool
    {
        return $this->sentido === self::MENOR_MEJOR ? $this->valor <= $this->meta : $this->valor >= $this->meta;
    }

    /** @return array<string, string|float|bool> */
    public function aArreglo(): array
    {
        return ['clave' => $this->clave, 'titulo' => $this->titulo, 'valor' => round($this->valor, 2),
            'meta' => $this->meta, 'sentido' => $this->sentido, 'cumple_meta' => $this->cumpleMeta()];
    }
}
