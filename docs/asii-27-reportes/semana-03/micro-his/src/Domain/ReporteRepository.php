<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Domain;

/**
 * Puerto de lectura de filas del reporte. Definido en el Dominio.
 */
interface ReporteRepository
{
    /**
     * @return list<array<string, scalar>>
     */
    public function entreFechas(string $desde, string $hasta): array;
}
