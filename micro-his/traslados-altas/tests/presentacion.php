<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Presentation\ConsolaTraslados;
use MicroHis\TrasladosAltas\Presentation\VistaResultado;

// Controller y vista sobre el doble: cada resultado tiene su código de salida y solo el éxito dice "exito".
function consola(RegistroFalso $h, array $argumentos): array
{
    return (new ConsolaTraslados($h, new AutorizadorFijo(true), ACTOR))->atender($argumentos);
}

$r = consola(hospitalFalso(), ['trasladar', ESTANCIA, CAMA_B, 'Aislamiento']);
check('trasladar → código 0 con estancia destino y episodio abierto', $r['codigo'] === VistaResultado::EXITO && $r['cuerpo']['cama_liberada_id'] === CAMA_A
    && $r['cuerpo']['episodio_abierto_id'] === EPISODIO && isset($r['cuerpo']['estancia_destino_id']));
$r = consola(hospitalFalso(), ['alta', ESTANCIA]);
check('alta → código 0 con episodio cerrado', $r['codigo'] === VistaResultado::EXITO && $r['cuerpo']['episodio_cerrado_id'] === EPISODIO);

$r = consola(hospitalFalso(), ['trasladar', ESTANCIA, CAMA_D, 'Aislamiento']);
check('rechazo → código 1 y motivo', $r['codigo'] === VistaResultado::RECHAZO && $r['cuerpo']['motivo'] === 'DESTINO_OCUPADO');
$h = hospitalFalso();
$h->fallarEn = 'escribir';
$r = consola($h, ['alta', ESTANCIA]);
check('fallo → código 2 sin decir éxito', $r['codigo'] === VistaResultado::FALLO && $r['cuerpo']['resultado'] === 'fallo');
$h = hospitalFalso();
$h->fallarEn = 'incierto';
$r = consola($h, ['alta', ESTANCIA]);
check('incierto → código 3 y pide consultar antes de reintentar', $r['codigo'] === VistaResultado::INCIERTO
    && str_contains($r['cuerpo']['mensaje'], 'antes de reintentar'));

foreach ([[['mover', ESTANCIA], 'operacion'], [['trasladar', ESTANCIA, CAMA_B], 'motivo'], [['alta'], 'estancia_esperada']] as [$argumentos, $campo]) {
    $h = hospitalFalso();
    $r = consola($h, $argumentos);
    check("argumentos sin {$campo} → código 64 sin tocar HOSPITAL", $r['codigo'] === VistaResultado::SOLICITUD_INVALIDA
        && $r['cuerpo']['campo'] === $campo && $h->llamadas === []);
}
