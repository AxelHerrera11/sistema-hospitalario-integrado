<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Persistence;

use MicroHIS\Reportes\Domain\Exportador;

/**
 * Exportador CSV. Formato nuevo = nueva clase; no se modifica el caso de uso (OCP).
 */
final class ExportadorCsv implements Exportador
{
    public function formato(): string
    {
        return 'csv';
    }

    public function exportar(array $filas): string
    {
        if ($filas === []) {
            return '';
        }
        $lineas = [implode(',', array_keys($filas[0]))];
        foreach ($filas as $fila) {
            $lineas[] = implode(',', array_map(static fn ($v): string => (string) $v, array_values($fila)));
        }
        return implode("\n", $lineas) . "\n";
    }
}
