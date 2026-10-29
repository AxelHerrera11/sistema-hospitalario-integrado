<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Infrastructure;

use MicroHIS\Reportes\Domain\Reporte;
use MicroHIS\Reportes\Domain\ReporteRepository;

// Adaptador Repository en memoria (pruebas y demos sin base de datos).
final class ReporteRepositoryInMemory implements ReporteRepository
{
    private array $reportes = [];

    public function porPeriodo(string $periodo): array
    {
        $resultado = [];
        foreach ($this->reportes as $reporte) {
            if ($reporte->periodo === $periodo) {
                $resultado[] = $reporte;
            }
        }
        return $resultado;
    }

    public function guardar(Reporte $reporte): void
    {
        $this->reportes[$reporte->id] = $reporte;
    }
}
