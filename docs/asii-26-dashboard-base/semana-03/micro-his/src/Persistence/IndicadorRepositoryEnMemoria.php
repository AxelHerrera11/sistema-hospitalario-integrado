<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Persistence;

use MicroHIS\Dashboard\Domain\Indicador;
use MicroHIS\Dashboard\Domain\IndicadorRepository;
use MicroHIS\Dashboard\Domain\ErrorPersistenciaException;

/**
 * Doble en memoria de IndicadorRepository para pruebas.
 */
final class IndicadorRepositoryEnMemoria implements IndicadorRepository
{
    /** @var list<Indicador> */
    private array $indicadores = [];

    public function agregar(Indicador $indicador): void
    {
        $this->indicadores[] = $indicador;
    }

    public function todos(): array
    {
        return $this->indicadores;
    }
}

/**
 * Doble que siempre falla, para verificar la traduccion del error de persistencia.
 */
final class IndicadorRepositoryQueFalla implements IndicadorRepository
{
    public function todos(): array
    {
        throw new ErrorPersistenciaException('fallo simulado de persistencia');
    }
}
