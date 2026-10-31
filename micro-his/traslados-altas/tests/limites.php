<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;

// Semana 15, caja negra: valores límite y clases de equivalencia que no cubrían las suites anteriores.
$motivo = static fn (string $m): ?string => (new SolicitudTraslado(ESTANCIA, CAMA_B, $m))->motivo;
check('B-L1 motivo de 1 carácter (límite inferior válido) → aceptado', $motivo('x') === 'x');
check('B-L2 motivo de exactamente 500 caracteres (límite superior válido) → aceptado', mb_strlen((string) $motivo(str_repeat('á', 500))) === 500);
check('B-L3 motivo de 500 caracteres con espacios alrededor → se recorta y se acepta', mb_strlen((string) $motivo('  ' . str_repeat('m', 500) . '  ')) === 500);

$h = hospitalFalso();
check('B-E1 X-Actor-Ref solo con espacios → 401 sin tocar HOSPITAL', http($h, 'POST', '/altas', '   ', ['estancia_esperada_id' => ESTANCIA])['estado'] === 401 && $h->llamadas === []);
$c = http(hospitalFalso(), 'GET', '/estancias/' . strtoupper(ESTANCIA) . '/internacion');
check('B-E2 consulta con el uuid en mayúsculas → 200 y la estancia se reconoce como abierta', $c['estado'] === 200 && $c['cuerpo']['estancia_consultada_abierta'] === true);
check('B-E3 POST /altas con un cuerpo JSON que no es objeto ("[]" y "1") → 422 o 400, nunca éxito',
    !in_array(http(hospitalFalso(), 'POST', '/altas', cuerpo: '[]')['estado'], [200, 201], true) && http(hospitalFalso(), 'POST', '/altas', cuerpo: '1')['estado'] === 400);
