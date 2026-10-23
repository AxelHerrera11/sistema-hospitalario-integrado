<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Application;

use MicroHIS\Dashboard\Domain\IndicadorRepository;

/**
 * Caso de uso: construir el resumen del dashboard a partir de los indicadores.
 */
final class ResumenDashboard
{
    public function __construct(private readonly IndicadorRepository $repositorio)
    {
    }

    /** @return list<array{clave:string,etiqueta:string,valor:int}> */
    public function ejecutar(): array
    {
        return array_map(
            static fn ($i): array => ['clave' => $i->clave, 'etiqueta' => $i->etiqueta, 'valor' => $i->valor],
            $this->repositorio->todos()
        );
    }
}
