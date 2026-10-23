<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Domain;

final class FiltroInvalidoException extends \RuntimeException
{
}

final class FormatoNoSoportadoException extends \RuntimeException
{
}

final class ErrorPersistenciaException extends \RuntimeException
{
}

/**
 * Entidad Reporte. Regla central: el rango de fechas del filtro nunca puede estar invertido.
 */
final class Reporte
{
    /**
     * @param list<array<string, scalar>> $filas
     */
    private function __construct(
        public readonly string $desde,
        public readonly string $hasta,
        public readonly array $filas
    ) {
    }

    /**
     * @param list<array<string, scalar>> $filas
     */
    public static function construir(string $desde, string $hasta, array $filas): self
    {
        if ($desde > $hasta) {
            throw new FiltroInvalidoException('422 rango_fechas_invalido');
        }
        return new self($desde, $hasta, $filas);
    }

    public function total(): int
    {
        return count($this->filas);
    }
}
