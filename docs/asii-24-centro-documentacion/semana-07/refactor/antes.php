<?php

declare(strict_types=1);
/* ANTES: cadena if/elseif por rol, con el mismo filtro copiado en cada rama. */

function listarDocumentosAntes(array $documentos, string $rol): array
{
    $salida = [];
    foreach ($documentos as $d) {
        if ($rol == 'admin') {
            $salida[] = $d['titulo'];
        } elseif ($rol == 'medico') {
            if (in_array('medico', $d['roles'])) {
                $salida[] = $d['titulo'];
            }
        } elseif ($rol == 'enfermeria') {
            if (in_array('enfermeria', $d['roles'])) {
                $salida[] = $d['titulo'];
            }
        } elseif ($rol == 'farmacia') {
            if (in_array('farmacia', $d['roles'])) {
                $salida[] = $d['titulo'];
            }
        }
    }
    return $salida;
}
