<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Domain\SolicitudAlta;
use MicroHis\TrasladosAltas\Domain\SolicitudInvalida;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

// RF-TA-01 y RF-TA-02: la solicitud se construye completa o no se construye.
const ESTANCIA = '00000000-0000-4000-8000-0000000000e1';
const CAMA_B = '00000000-0000-4000-8000-0000000000b2';

$traslado = new SolicitudTraslado(' ' . strtoupper(ESTANCIA) . ' ', CAMA_B, '  Requiere aislamiento  ');
check('traslado válido normaliza ids y recorta el motivo', $traslado->estanciaEsperadaId === ESTANCIA && $traslado->motivo === 'Requiere aislamiento');
check('alta válida solo necesita la estancia esperada', (new SolicitudAlta(ESTANCIA))->estanciaEsperadaId === ESTANCIA);

foreach ([
    'estancia_esperada' => fn () => new SolicitudTraslado('', CAMA_B, 'motivo'),
    'cama_destino' => fn () => new SolicitudTraslado(ESTANCIA, 'cama-7', 'motivo'),
    'motivo' => fn () => new SolicitudTraslado(ESTANCIA, CAMA_B, "   \n "),
] as $campo => $construir) {
    check("traslado sin {$campo} válido → SolicitudInvalida({$campo})", thrown($construir, SolicitudInvalida::class)?->campo === $campo);
}
check('motivo de más de 500 caracteres → SolicitudInvalida', thrown(fn () => new SolicitudTraslado(ESTANCIA, CAMA_B, str_repeat('m', 501)), SolicitudInvalida::class) !== null);
check('alta con id que no es uuid → SolicitudInvalida', thrown(fn () => new SolicitudAlta('1 OR 1=1'), SolicitudInvalida::class)?->campo === 'estancia_esperada');
