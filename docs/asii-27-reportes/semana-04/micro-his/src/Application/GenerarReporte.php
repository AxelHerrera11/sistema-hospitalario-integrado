<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Application;

use MicroHIS\Reportes\Domain\Reporte;
use MicroHIS\Reportes\Domain\ReporteRepository;

// Caso de uso: generar reporte operativo para un periodo.
final class GenerarReporte
{
    public function __construct(private readonly ReporteRepository $repositorio)
    {
    }

    public function ejecutar(string $periodo, array $datos): Reporte
    {
        $reporte = Reporte::generar('rep-' . bin2hex(random_bytes(3)), $periodo, $datos);
        $this->repositorio->guardar($reporte);
        return $reporte;
    }

    public function listar(string $periodo): array
    {
        return array_values(array_map(
            static fn (Reporte $r): array => ['id' => $r->id, 'periodo' => $r->periodo, 'contenido' => $r->contenido],
            $this->repositorio->porPeriodo($periodo)
        ));
    }
}
