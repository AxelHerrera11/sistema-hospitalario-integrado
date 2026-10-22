<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\Rechazo;
use MicroHis\TrasladosAltas\Domain\ReglasLiberacion;

// RF-TA-04: reglas sobre la estancia esperada, en el orden en que se evalúan.
const EPISODIO = '00000000-0000-4000-8000-0000000000c1';
const CAMA_A = '00000000-0000-4000-8000-0000000000b1';

function internacion(array $cambios = []): EstadoInternacion
{
    $base = ['episodioId' => EPISODIO, 'tipoEpisodio' => 'inpatient', 'estadoEpisodio' => 'open',
        'inicioEpisodio' => new DateTimeImmutable('2026-10-01T08:00:00Z'), 'estanciaAbiertaId' => ESTANCIA,
        'camaActualId' => CAMA_A, 'inicioEstancia' => new DateTimeImmutable('2026-10-01T09:00:00Z')];

    return new EstadoInternacion(...array_merge($base, $cambios));
}

$t = new DateTimeImmutable('2026-10-02T10:00:00Z');
check('estancia abierta del episodio hospitalario abierto → sin rechazo', ReglasLiberacion::rechazoDeEstancia(internacion(), ESTANCIA, $t) === null);
foreach ([
    'episodio ambulatorio' => [['tipoEpisodio' => 'outpatient'], Rechazo::EpisodioNoHospitalario],
    'episodio cerrado' => [['estadoEpisodio' => 'closed'], Rechazo::EpisodioNoAbierto],
    'episodio sin estancia abierta' => [['estanciaAbiertaId' => null, 'camaActualId' => null, 'inicioEstancia' => null], Rechazo::SinEstanciaAbierta],
    'otra estancia abierta (el usuario vio datos viejos)' => [['estanciaAbiertaId' => '00000000-0000-4000-8000-0000000000e2'], Rechazo::EstanciaNoVigente],
] as $caso => [$cambios, $esperado]) {
    check("{$caso} → {$esperado->value}", ReglasLiberacion::rechazoDeEstancia(internacion($cambios), ESTANCIA, $t) === $esperado);
}
// Límites de T: igual al inicio de la estancia se rechaza; un microsegundo después ya es posterior.
$inicio = new DateTimeImmutable('2026-10-01T09:00:00Z');
check('T igual al inicio de la estancia → MOMENTO_NO_POSTERIOR', ReglasLiberacion::rechazoDeEstancia(internacion(), ESTANCIA, $inicio) === Rechazo::MomentoNoPosterior);
check('T anterior al inicio del episodio → MOMENTO_NO_POSTERIOR', ReglasLiberacion::rechazoDeEstancia(internacion(), ESTANCIA, new DateTimeImmutable('2026-09-30T00:00:00Z')) === Rechazo::MomentoNoPosterior);
check('T un microsegundo después del inicio → sin rechazo', ReglasLiberacion::rechazoDeEstancia(internacion(), ESTANCIA, $inicio->modify('+1 microsecond')) === null);
