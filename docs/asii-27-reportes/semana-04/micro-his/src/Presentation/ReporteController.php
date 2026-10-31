<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Presentation;

use MicroHIS\Reportes\Application\GenerarReporte;
use MicroHIS\Reportes\Domain\ReporteInvalidoException;

// Controlador MVC. No contiene SQL ni reglas: orquesta casos de uso y traduce errores a HTTP.
final class ReporteController
{
    public function __construct(private readonly GenerarReporte $generarReporte)
    {
    }

    public function generar(array $entrada): array
    {
        $periodo = (string) ($entrada['periodo'] ?? '');
        try {
            $reporte = $this->generarReporte->ejecutar($periodo, (array) ($entrada['datos'] ?? []));
        } catch (ReporteInvalidoException $e) {
            return ['status' => (int) substr($e->getMessage(), 0, 3), 'body' => ['error' => substr($e->getMessage(), 4)]];
        }
        return ['status' => 201, 'body' => ['id' => $reporte->id]];
    }

    public function listar(array $entrada): array
    {
        $periodo = (string) ($entrada['periodo'] ?? '');
        if ($periodo === '') {
            return ['status' => 422, 'body' => ['error' => 'periodo_requerido']];
        }
        return ['status' => 200, 'body' => ['reportes' => $this->generarReporte->listar($periodo)]];
    }
}
