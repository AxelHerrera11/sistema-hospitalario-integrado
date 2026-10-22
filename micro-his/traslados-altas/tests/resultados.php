<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\ExitoConfirmado;
use MicroHis\TrasladosAltas\Application\FalloConfirmado;
use MicroHis\TrasladosAltas\Application\RechazoConfirmado;
use MicroHis\TrasladosAltas\Application\ResultadoIncierto;
use MicroHis\TrasladosAltas\Application\ResultadoOperacion;
use MicroHis\TrasladosAltas\Domain\LiberacionDeCama;
use MicroHis\TrasladosAltas\Domain\Rechazo;

// RF-TA-08 y RNF-TA-06: solo el éxito confirmado declara cambios; el incierto no es éxito.
$resultados = [
    'éxito' => new ExitoConfirmado(new LiberacionDeCama(CAMA_A, ESTANCIA, new DateTimeImmutable('2026-10-02T10:00:00Z'))),
    'rechazo' => new RechazoConfirmado(Rechazo::EstanciaNoVigente),
    'fallo' => new FalloConfirmado('PERSISTENCIA'),
    'incierto' => new ResultadoIncierto(ESTANCIA),
];
$confirmados = array_keys(array_filter($resultados, static fn (ResultadoOperacion $r): bool => $r->confirmoCambios()));
check('de los cuatro resultados solo el éxito confirma cambios', $confirmados === ['éxito']);
check('el resultado incierto conserva la estancia para consultar el estado', $resultados['incierto']->estanciaEsperadaId === ESTANCIA);
