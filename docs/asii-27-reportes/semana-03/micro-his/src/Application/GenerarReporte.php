<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Application;

use MicroHIS\Reportes\Domain\Exportador;
use MicroHIS\Reportes\Domain\FormatoNoSoportadoException;
use MicroHIS\Reportes\Domain\Reporte;
use MicroHIS\Reportes\Domain\ReporteRepository;

/**
 * Caso de uso: construir y exportar un reporte operativo.
 * Aplicando OCP, recibe los exportadores por inyeccion: un formato nuevo se agrega sin
 * modificar este codigo.
 */
final class GenerarReporte
{
    /**
     * @param list<Exportador> $exportadores
     */
    public function __construct(
        private readonly ReporteRepository $repositorio,
        private readonly array $exportadores
    ) {
    }

    public function ejecutar(string $desde, string $hasta, string $formato): string
    {
        $filas = $this->repositorio->entreFechas($desde, $hasta);
        $reporte = Reporte::construir($desde, $hasta, $filas);
        return $this->exportador($formato)->exportar($reporte->filas);
    }

    private function exportador(string $formato): Exportador
    {
        foreach ($this->exportadores as $exportador) {
            if ($exportador->formato() === $formato) {
                return $exportador;
            }
        }
        throw new FormatoNoSoportadoException('406 report_format_unsupported: ' . $formato);
    }
}
