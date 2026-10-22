<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\ExitoConfirmado;
use MicroHis\TrasladosAltas\Application\FalloConfirmado;
use MicroHis\TrasladosAltas\Application\Operacion;
use MicroHis\TrasladosAltas\Application\ProtocoloLiberacion;
use MicroHis\TrasladosAltas\Application\RechazoConfirmado;
use MicroHis\TrasladosAltas\Application\ResultadoIncierto;
use MicroHis\TrasladosAltas\Domain\LiberacionDeCama;
use MicroHis\TrasladosAltas\Domain\Rechazo;

require_once __DIR__ . '/RegistroFalso.php';

const ACTOR = '00000000-0000-4000-8000-0000000000f1';
const CAMA_C = '00000000-0000-4000-8000-0000000000b3';
const CAMA_D = '00000000-0000-4000-8000-0000000000b4';

/** Episodio hospitalario abierto con su estancia en la cama A; B libre, C en mantenimiento y D ocupada por otro episodio. */
function hospitalFalso(): RegistroFalso
{
    $h = new RegistroFalso(new DateTimeImmutable('2026-10-02T10:00:00Z'));
    $h->episodios = [EPISODIO => ['tipo' => 'inpatient', 'estado' => 'open', 'inicio' => new DateTimeImmutable('2026-10-01T08:00:00Z'), 'fin' => null],
        'otro' => ['tipo' => 'inpatient', 'estado' => 'open', 'inicio' => new DateTimeImmutable('2026-10-01T08:00:00Z'), 'fin' => null]];
    $h->estancias = [ESTANCIA => ['episodio' => EPISODIO, 'cama' => CAMA_A, 'inicio' => new DateTimeImmutable('2026-10-01T09:00:00Z'), 'fin' => null],
        'estancia-d' => ['episodio' => 'otro', 'cama' => CAMA_D, 'inicio' => new DateTimeImmutable('2026-10-01T09:00:00Z'), 'fin' => null]];
    $h->camas = [CAMA_A => 'available', CAMA_B => 'available', CAMA_C => 'maintenance', CAMA_D => 'available'];

    return $h;
}

/** Ejecuta el protocolo con una decisión que cierra la estancia, como lo haría una operación real. */
function protocolo(RegistroFalso $h, bool $permite = true): mixed
{
    return (new ProtocoloLiberacion($h, new AutorizadorFijo($permite)))->ejecutar(Operacion::Alta, ACTOR, ESTANCIA,
        static fn ($estado, DateTimeImmutable $t) => static function () use ($h, $t): LiberacionDeCama {
            $h->cerrarEstancia(ESTANCIA, $t);

            return new LiberacionDeCama(CAMA_A, ESTANCIA, $t);
        });
}

$h = hospitalFalso();
$r = protocolo($h, false);
check('sin autorización → NO_AUTORIZADO sin abrir la transacción (RF-TA-03)', $r instanceof RechazoConfirmado && $r->motivo === Rechazo::NoAutorizado && $h->llamadas === []);

$h = hospitalFalso();
$antes = $h->estado();
$r = (new ProtocoloLiberacion($h, new AutorizadorFijo(true)))->ejecutar(Operacion::Alta, ACTOR, '00000000-0000-4000-8000-000000000999', static fn () => null);
check('estancia inexistente → ESTANCIA_INEXISTENTE, revierte y no cambia nada', $r instanceof RechazoConfirmado && $r->motivo === Rechazo::EstanciaInexistente
    && end($h->llamadas) === 'revertir' && $h->estado() === $antes);

foreach (['iniciar', 'leer', 'escribir', 'confirmar'] as $punto) {
    $h = hospitalFalso();
    $antes = $h->estado();
    $h->fallarEn = $punto;
    $r = protocolo($h);
    check("falla de HOSPITAL en {$punto} → FalloConfirmado y estado sin cambios", $r instanceof FalloConfirmado && $h->estado() === $antes);
}

$h = hospitalFalso();
$h->fallarEn = 'incierto';
$r = protocolo($h);
check('COMMIT sin respuesta → ResultadoIncierto, sin revertir ni reintentar (RNF-TA-06)', $r instanceof ResultadoIncierto
    && !in_array('revertir', $h->llamadas, true) && count(array_keys($h->llamadas, 'confirmar', true)) === 1);

$r = protocolo($h = hospitalFalso());
check('camino feliz del protocolo → ExitoConfirmado después de confirmar', $r instanceof ExitoConfirmado && end($h->llamadas) === 'confirmar');
