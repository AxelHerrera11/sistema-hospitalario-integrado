<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Domain;

final class ReporteInvalidoException extends \RuntimeException {}
final class ErrorPersistenciaException extends \RuntimeException {}

// Entidad Reporte. Genera un reporte inmutable para un periodo.
final class Reporte
{
    private function __construct(
        public readonly string $id,
        public readonly string $periodo,
        public readonly array $contenido
    ) {
    }

    public static function generar(string $id, string $periodo, array $contenido): self
    {
        if ($periodo === '') {
            throw new ReporteInvalidoException('422 reporte_invalido');
        }
        return new self($id, $periodo, $contenido);
    }
}
