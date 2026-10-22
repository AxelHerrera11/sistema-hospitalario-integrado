<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Domain\CamaDestino;
use MicroHis\TrasladosAltas\Domain\Rechazo;
use MicroHis\TrasladosAltas\Domain\ReglasLiberacion;

// RF-TA-05: reglas de la cama destino, solo para el traslado.
check('cama disponible y libre desde T → sin rechazo', ReglasLiberacion::rechazoDeDestino(new CamaDestino(CAMA_B, 'available', true), internacion()) === null);
foreach ([
    'cama inexistente' => [null, Rechazo::DestinoInexistente],
    'misma cama de origen' => [new CamaDestino(CAMA_A, 'available', true), Rechazo::DestinoIgualOrigen],
    'cama con estancia que se solapa con [T, ∞)' => [new CamaDestino(CAMA_B, 'available', false), Rechazo::DestinoOcupado],
    'cama en mantenimiento (R-TA-01 propuesta)' => [new CamaDestino(CAMA_B, 'maintenance', true), Rechazo::DestinoNoDisponible],
] as $caso => [$destino, $esperado]) {
    check("{$caso} → {$esperado->value}", ReglasLiberacion::rechazoDeDestino($destino, internacion()) === $esperado);
}
// El solape se evalúa antes que el estado: una cama ocupada e inactiva informa la causa más concreta.
check('ocupada e inactiva → DESTINO_OCUPADO', ReglasLiberacion::rechazoDeDestino(new CamaDestino(CAMA_B, 'inactive', false), internacion()) === Rechazo::DestinoOcupado);
