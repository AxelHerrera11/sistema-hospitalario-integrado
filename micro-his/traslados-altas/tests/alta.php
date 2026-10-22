<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\AltaHospitalaria;
use MicroHis\TrasladosAltas\Application\ExitoConfirmado;
use MicroHis\TrasladosAltas\Application\FalloConfirmado;
use MicroHis\TrasladosAltas\Application\OperacionConLiberacionDeCama;
use MicroHis\TrasladosAltas\Application\RechazoConfirmado;
use MicroHis\TrasladosAltas\Application\TrasladoInterno;
use MicroHis\TrasladosAltas\Domain\Rechazo;
use MicroHis\TrasladosAltas\Domain\SolicitudAlta;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

// UC-TA-02 con el doble y batería LSP común (RNF-TA-01).
function darAlta(RegistroFalso $h): mixed
{
    return (new AltaHospitalaria(new SolicitudAlta(ESTANCIA), ACTOR, $h, new AutorizadorFijo(true)))->ejecutar();
}

$h = hospitalFalso();
$t = new DateTimeImmutable('2026-10-02T10:00:00Z');
check('alta → ExitoConfirmado', darAlta($h) instanceof ExitoConfirmado);
check('estancia y episodio cerrados con la misma marca temporal', $h->estancias[ESTANCIA]['fin'] == $t && $h->episodios[EPISODIO]['fin'] == $t
    && $h->episodios[EPISODIO]['estado'] === 'closed');
check('el alta no deja estancias abiertas del episodio ni cambia beds.status', array_filter($h->estancias, static fn ($s) => $s['episodio'] === EPISODIO && $s['fin'] === null) === []
    && $h->camas[CAMA_A] === 'available');
$antes = $h->estado();
$r = darAlta($h);
check('repetir el alta → EPISODIO_NO_ABIERTO sin cambios', $r instanceof RechazoConfirmado && $r->motivo === Rechazo::EpisodioNoAbierto && $h->estado() === $antes);

/** Cliente común: solo conoce el contrato y LiberacionDeCama; no usa instanceof sobre la operación. */
function informar(OperacionConLiberacionDeCama $operacion): string
{
    $resultado = $operacion->ejecutar();

    return $resultado instanceof ExitoConfirmado ? "liberada {$resultado->liberacion->camaOrigenId}" : $resultado::class;
}

$fabricas = [
    'traslado' => static fn (RegistroFalso $h) => new TrasladoInterno(new SolicitudTraslado(ESTANCIA, CAMA_B, 'Aislamiento'), ACTOR, $h, new AutorizadorFijo(true)),
    'alta' => static fn (RegistroFalso $h) => new AltaHospitalaria(new SolicitudAlta(ESTANCIA), ACTOR, $h, new AutorizadorFijo(true)),
];
foreach ($fabricas as $nombre => $fabrica) {
    check("LSP {$nombre}: el cliente común informa la cama A liberada", informar($fabrica(hospitalFalso())) === 'liberada ' . CAMA_A);
    $h = hospitalFalso();
    $antes = $h->estado();
    $h->fallarEn = 'escribir';
    check("LSP {$nombre}: con HOSPITAL fallando, FalloConfirmado y sin cambios", informar($fabrica($h)) === FalloConfirmado::class && $h->estado() === $antes);
}
