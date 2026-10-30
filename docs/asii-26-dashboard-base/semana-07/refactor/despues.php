<?php

declare(strict_types=1);
/* DESPUES: tabla de definiciones (datos) + un solo calculo de semaforo con umbrales con nombre. */

namespace Refactor\Dashboard;

final class Semaforo
{
    public const UMBRAL_VERDE = 100.0;
    public const UMBRAL_AMARILLO = 90.0;

    public static function color(float $cumplimiento): string
    {
        return match (true) {
            $cumplimiento >= self::UMBRAL_VERDE => 'verde',
            $cumplimiento >= self::UMBRAL_AMARILLO => 'amarillo',
            default => 'rojo',
        };
    }
}

/** clave => [titulo, unidad, menorEsMejor] */
const DEFINICIONES = [
    'ocupacion' => ['Ocupacion de camas', '%', false],
    'espera' => ['Tiempo de espera', 'min', true],
    'citas_atendidas' => ['Citas atendidas', 'citas', false],
];

function cumplimiento(float $valor, float $meta, bool $menorEsMejor): float
{
    return $menorEsMejor ? $meta / $valor * 100 : $valor / $meta * 100;
}

function tarjetasDespues(array $indicadores): array
{
    $tarjetas = [];
    foreach ($indicadores as $i) {
        if (!isset(DEFINICIONES[$i['clave']])) {
            continue;
        }
        [$titulo, $unidad, $menorEsMejor] = DEFINICIONES[$i['clave']];
        $color = Semaforo::color(cumplimiento($i['valor'], $i['meta'], $menorEsMejor));
        $tarjetas[] = ['titulo' => $titulo, 'texto' => $i['valor'] . ' ' . $unidad, 'color' => $color];
    }
    return $tarjetas;
}
