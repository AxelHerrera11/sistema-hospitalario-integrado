<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Domain;

// Interfaz Repository en el limite del Dominio (patron Repository).
interface IndicadorRepository
{
    public function porPeriodo(string $periodo): array;

    public function guardar(Indicador $indicador): void;
}
