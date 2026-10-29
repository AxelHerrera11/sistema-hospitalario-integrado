<?php

declare(strict_types=1);
/* ANTES: un if/elseif por formato; agregar uno obliga a editar esta funcion. */

function exportarAntes(array $filas, string $formato): string
{
    if ($formato == 'csv') {
        $salida = implode(',', array_keys($filas[0])) . "\n";
        foreach ($filas as $f) {
            $salida .= implode(',', $f) . "\n";
        }
        return $salida;
    } elseif ($formato == 'tsv') {
        $salida = implode("\t", array_keys($filas[0])) . "\n";
        foreach ($filas as $f) {
            $salida .= implode("\t", $f) . "\n";
        }
        return $salida;
    } elseif ($formato == 'json') {
        return json_encode(['data' => $filas, 'total' => count($filas)]);
    } else {
        return 'ERROR: report_format_unsupported';
    }
}
