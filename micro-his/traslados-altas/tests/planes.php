<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Domain\LiberacionDeCama;
use MicroHis\TrasladosAltas\Domain\PlanAlta;
use MicroHis\TrasladosAltas\Domain\PlanTraslado;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

// RF-TA-06, RF-TA-07 y RF-TA-09: qué se cierra, qué se abre y qué informa el éxito.
$t = new DateTimeImmutable('2026-10-02T10:00:00Z');
$plan = PlanTraslado::desde(internacion(), new SolicitudTraslado(ESTANCIA, CAMA_B, 'Requiere aislamiento'), $t);
check('traslado: cierra la estancia de origen y abre en la cama destino, mismo episodio y mismo T',
    [$plan->estanciaOrigenId, $plan->camaOrigenId, $plan->camaDestinoId, $plan->episodioId, $plan->t] === [ESTANCIA, CAMA_A, CAMA_B, EPISODIO, $t]);
$traslado = $plan->resultado('00000000-0000-4000-8000-0000000000e2');
check('el éxito del traslado incluye la nueva estancia y el episodio que sigue abierto',
    $traslado->estanciaDestinoId === '00000000-0000-4000-8000-0000000000e2' && $traslado->episodioAbiertoId === EPISODIO);

$alta = PlanAlta::desde(internacion(), $t)->resultado();
check('alta: estancia y episodio se cierran con la misma marca temporal', $alta->t === $t && $alta->episodioCerradoId === EPISODIO && $alta->estanciaCerradaId === ESTANCIA);

// LSP (RNF-TA-01): un cliente que solo conoce LiberacionDeCama lee igual ambos resultados.
$liberadas = array_map(static fn (LiberacionDeCama $l): string => "{$l->camaOrigenId}|{$l->estanciaCerradaId}|{$l->t->format('c')}", [$traslado, $alta]);
check('traslado y alta exponen la misma liberación común', $liberadas[0] === $liberadas[1]);
