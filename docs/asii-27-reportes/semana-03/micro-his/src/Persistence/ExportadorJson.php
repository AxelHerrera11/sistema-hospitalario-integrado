<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Persistence;

use MicroHIS\Reportes\Domain\Exportador;

/**
 * Exportador JSON. Se agrega como formato nuevo sin tocar el caso de uso (OCP).
 */
final class ExportadorJson implements Exportador
{
    public function formato(): string
    {
        return 'json';
    }

    public function exportar(array $filas): string
    {
        return json_encode(['total' => count($filas), 'filas' => $filas], JSON_THROW_ON_ERROR) . "\n";
    }
}
