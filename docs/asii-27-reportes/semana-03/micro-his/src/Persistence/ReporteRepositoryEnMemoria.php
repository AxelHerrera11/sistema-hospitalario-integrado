<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Persistence;

use MicroHIS\Reportes\Domain\ReporteRepository;

/**
 * Doble en memoria de ReporteRepository para pruebas.
 */
final class ReporteRepositoryEnMemoria implements ReporteRepository
{
    /**
     * @param list<array<string, scalar>> $filas
     */
    public function __construct(private array $filas = [])
    {
    }

    /**
     * @param array<string, scalar> $fila
     */
    public function agregar(array $fila): void
    {
        $this->filas[] = $fila;
    }

    public function entreFechas(string $desde, string $hasta): array
    {
        return array_values(array_filter(
            $this->filas,
            static fn (array $f): bool => ($f['fecha'] ?? '') >= $desde && ($f['fecha'] ?? '') <= $hasta
        ));
    }
}
