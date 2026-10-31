<?php

declare(strict_types=1);
/* ANTES: una funcion valida, detecta solape, decide estados y arma la respuesta (todo junto). */

function agendarCitaAntes(array $datos, array &$citas): array
{
    if (!isset($datos['paciente']) || !isset($datos['doctor']) || !isset($datos['desde']) || !isset($datos['hasta'])) {
        return ['status' => 422, 'error' => 'validation_failed'];
    }
    if ($datos['desde'] >= $datos['hasta']) {
        return ['status' => 409, 'error' => 'cita_rango_invalido'];
    }
    foreach ($citas as $c) {
        if ($c['estado'] == 'scheduled' || $c['estado'] == 'confirmed') {
            if ($c['paciente'] == $datos['paciente'] || $c['doctor'] == $datos['doctor']) {
                if ($c['desde'] < $datos['hasta'] && $datos['desde'] < $c['hasta']) {
                    return ['status' => 409, 'error' => 'appointments_overlap'];
                }
            }
        }
    }
    $citas[] = ['paciente' => $datos['paciente'], 'doctor' => $datos['doctor'], 'desde' => $datos['desde'], 'hasta' => $datos['hasta'], 'estado' => 'scheduled'];
    return ['status' => 201, 'error' => null];
}
