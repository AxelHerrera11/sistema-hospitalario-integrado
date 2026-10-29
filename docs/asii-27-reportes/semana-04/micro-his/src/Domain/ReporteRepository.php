<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Domain;

// Interfaz Repository en el limite del Dominio (patron Repository).
interface ReporteRepository
{
    public function porPeriodo(string $periodo): array;

    public function guardar(Reporte $reporte): void;
}
