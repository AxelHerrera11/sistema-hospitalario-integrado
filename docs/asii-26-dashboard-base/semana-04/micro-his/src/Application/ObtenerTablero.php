<?php

declare(strict_types=1);

namespace MicroHIS\Dashboard\Application;

use MicroHIS\Dashboard\Domain\IndicadorRepository;

// Caso de uso: armar el tablero de un periodo (read model, sin SQL ni HTTP).
final class ObtenerTablero
{
    public function __construct(private readonly IndicadorRepository $repositorio)
    {
    }

    public function ejecutar(string $periodo): array
    {
        $indicadores = $this->repositorio->porPeriodo($periodo);
        $cumplen = 0;
        $filas = [];
        foreach ($indicadores as $indicador) {
            if ($indicador->cumpleMeta()) {
                $cumplen++;
            }
            $filas[] = [
                'clave' => $indicador->clave,
                'valor' => $indicador->valor,
                'meta' => $indicador->meta,
                'cumple_meta' => $indicador->cumpleMeta(),
            ];
        }
        return ['periodo' => $periodo, 'total' => count($filas), 'cumplen_meta' => $cumplen, 'indicadores' => $filas];
    }
}
