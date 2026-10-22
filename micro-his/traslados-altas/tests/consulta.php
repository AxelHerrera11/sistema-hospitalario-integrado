<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\ConsultarInternacion;
use MicroHis\TrasladosAltas\Application\FalloPersistencia;
use MicroHis\TrasladosAltas\Application\NoAutorizado;
use MicroHis\TrasladosAltas\Application\Operacion;

// Consulta para resolver un resultado incierto: lee, siempre revierte y nunca escribe.
$h = hospitalFalso();
$antes = $h->estado();
$autorizador = new AutorizadorFijo(true);
$estado = (new ConsultarInternacion($h, $autorizador))->porEstancia(ACTOR, ESTANCIA);
check('consulta devuelve el episodio y su estancia abierta', $estado?->estanciaAbiertaId === ESTANCIA && $autorizador->consultada === Operacion::Consulta);
check('la consulta revierte su transacción y no cambia nada', end($h->llamadas) === 'revertir' && $h->estado() === $antes);
check('estancia inexistente → null', (new ConsultarInternacion(hospitalFalso(), $autorizador))->porEstancia(ACTOR, '00000000-0000-4000-8000-00000000dead') === null);

$h = hospitalFalso();
check('sin autorización → NoAutorizado sin abrir transacción', thrown(fn () => (new ConsultarInternacion($h, new AutorizadorFijo(false)))->porEstancia(ACTOR, ESTANCIA),
    NoAutorizado::class) !== null && $h->llamadas === []);
$h = hospitalFalso();
$h->fallarEn = 'leer';
check('HOSPITAL falla al leer → FalloPersistencia y la transacción se revierte', thrown(fn () => (new ConsultarInternacion($h, $autorizador))->porEstancia(ACTOR, ESTANCIA),
    FalloPersistencia::class) !== null && end($h->llamadas) === 'revertir');
