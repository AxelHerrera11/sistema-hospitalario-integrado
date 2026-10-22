<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\ExitoConfirmado;
use MicroHis\TrasladosAltas\Application\FalloConfirmado;
use MicroHis\TrasladosAltas\Application\Operacion;
use MicroHis\TrasladosAltas\Application\RechazoConfirmado;
use MicroHis\TrasladosAltas\Application\TrasladoInterno;
use MicroHis\TrasladosAltas\Domain\Rechazo;
use MicroHis\TrasladosAltas\Domain\ResultadoTraslado;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

// UC-TA-01 con el doble: camino feliz, rechazos del destino y falla a mitad de las escrituras.
function trasladar(RegistroFalso $h, string $destino, ?AutorizadorFijo $autorizador = null): mixed
{
    return (new TrasladoInterno(new SolicitudTraslado(ESTANCIA, $destino, 'Requiere aislamiento'), ACTOR, $h, $autorizador ?? new AutorizadorFijo(true)))->ejecutar();
}

$h = hospitalFalso();
$autorizador = new AutorizadorFijo(true);
$r = trasladar($h, CAMA_B, $autorizador);
$t = new DateTimeImmutable('2026-10-02T10:00:00Z');
$nueva = $r instanceof ExitoConfirmado && $r->liberacion instanceof ResultadoTraslado ? $h->estancias[$r->liberacion->estanciaDestinoId] : null;
check('traslado a cama libre → ExitoConfirmado con ResultadoTraslado', $nueva !== null && $autorizador->consultada === Operacion::Traslado);
check('la estancia de origen queda [inicio, T) y la de destino [T, ∞) en la cama B', $h->estancias[ESTANCIA]['fin'] == $t
    && $nueva['cama'] === CAMA_B && $nueva['inicio'] == $t && $nueva['fin'] === null);
check('el episodio sigue abierto y la estancia destino registra al actor', $h->episodios[EPISODIO]['estado'] === 'open' && $nueva['actor'] === ACTOR);
check('liberar la cama A no cambia beds.status (RF-TA-10)', $h->camas[CAMA_A] === 'available' && $h->camas[CAMA_B] === 'available');

foreach ([CAMA_D => Rechazo::DestinoOcupado, CAMA_C => Rechazo::DestinoNoDisponible, CAMA_A => Rechazo::DestinoIgualOrigen,
    '00000000-0000-4000-8000-0000000000bf' => Rechazo::DestinoInexistente] as $destino => $motivo) {
    $h = hospitalFalso();
    $antes = $h->estado();
    $r = trasladar($h, $destino);
    check("destino rechazado → {$motivo->value} sin cambios", $r instanceof RechazoConfirmado && $r->motivo === $motivo && $h->estado() === $antes);
}

// Falla al abrir la estancia destino después de cerrar la de origen: no queda un cierre a medias.
$h = hospitalFalso();
$antes = $h->estado();
$h->fallarEn = 'escribir2';
check('falla en la segunda escritura → FalloConfirmado y el origen vuelve a estar abierto', trasladar($h, CAMA_B) instanceof FalloConfirmado && $h->estado() === $antes);
