<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Infrastructure;

use MicroHIS\Dashboard\Domain\Indicador;
use MicroHIS\Dashboard\Domain\IndicadorRepository;

// Adaptador Repository en memoria (pruebas y demos sin base de datos).
final class IndicadorRepositoryInMemory implements IndicadorRepository
{
    private array $indicadores = [];

    public function porPeriodo(string $periodo): array
    {
        $resultado = [];
        foreach ($this->indicadores as $indicador) {
            if ($indicador->periodo === $periodo) {
                $resultado[] = $indicador;
            }
        }
        return $resultado;
    }

    public function guardar(Indicador $indicador): void
    {
        $this->indicadores[] = $indicador;
    }
}
