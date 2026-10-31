<?php

declare(strict_types=1);
/* ANTES: una rama por indicador, numeros magicos y semaforo copiado tres veces. */

function tarjetasAntes(array $indicadores): array
{
    $tarjetas = [];
    foreach ($indicadores as $i) {
        if ($i['clave'] == 'ocupacion') {
            $p = $i['valor'] / $i['meta'] * 100;
            if ($p >= 100) { $color = 'verde'; } elseif ($p >= 90) { $color = 'amarillo'; } else { $color = 'rojo'; }
            $tarjetas[] = ['titulo' => 'Ocupacion de camas', 'texto' => $i['valor'] . ' %', 'color' => $color];
        } elseif ($i['clave'] == 'espera') {
            $p = $i['meta'] / $i['valor'] * 100;
            if ($p >= 100) { $color = 'verde'; } elseif ($p >= 90) { $color = 'amarillo'; } else { $color = 'rojo'; }
            $tarjetas[] = ['titulo' => 'Tiempo de espera', 'texto' => $i['valor'] . ' min', 'color' => $color];
        } elseif ($i['clave'] == 'citas_atendidas') {
            $p = $i['valor'] / $i['meta'] * 100;
            if ($p >= 100) { $color = 'verde'; } elseif ($p >= 90) { $color = 'amarillo'; } else { $color = 'rojo'; }
            $tarjetas[] = ['titulo' => 'Citas atendidas', 'texto' => $i['valor'] . ' citas', 'color' => $color];
        }
    }
    return $tarjetas;
}
