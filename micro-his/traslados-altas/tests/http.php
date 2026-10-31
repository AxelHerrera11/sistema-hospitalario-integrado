<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Presentation\HttpTraslados;

// Prototipo HTTP sobre el doble: estados HTTP por resultado, autorización, rutas y la consulta para resolver un incierto.
function http(RegistroFalso $h, string $metodo, string $ruta, ?string $actor = ACTOR, array|string $cuerpo = [], bool $permite = true): array
{
    return (new HttpTraslados(static fn (): array => [$h, new AutorizadorFijo($permite)]))
        ->atender($metodo, $ruta, $actor, is_string($cuerpo) ? $cuerpo : (string) json_encode($cuerpo));
}

$traslado = ['estancia_esperada_id' => ESTANCIA, 'cama_destino_id' => CAMA_B, 'motivo' => 'Aislamiento'];
$r = http($h = hospitalFalso(), 'POST', '/traslados', cuerpo: $traslado);
check('POST /traslados → 201 con la estancia destino', $r['estado'] === 201 && isset($r['cuerpo']['estancia_destino_id']));
check('POST /altas → 200 con el episodio cerrado', http(hospitalFalso(), 'POST', '/altas', cuerpo: ['estancia_esperada_id' => ESTANCIA])['estado'] === 200);
check('rechazo de negocio → 409 con su motivo', http(hospitalFalso(), 'POST', '/traslados', cuerpo: ['cama_destino_id' => CAMA_D] + $traslado)['cuerpo']['motivo'] === 'DESTINO_OCUPADO');
check('actor sin permiso → 403 NO_AUTORIZADO', http(hospitalFalso(), 'POST', '/altas', cuerpo: ['estancia_esperada_id' => ESTANCIA], permite: false)['estado'] === 403);
check('sin X-Actor-Ref → 401 sin tocar HOSPITAL', http($h = hospitalFalso(), 'POST', '/altas', null, ['estancia_esperada_id' => ESTANCIA])['estado'] === 401 && $h->llamadas === []);
check('JSON inválido → 400; campo inválido → 422', http(hospitalFalso(), 'POST', '/altas', cuerpo: '{')['estado'] === 400
    && http(hospitalFalso(), 'POST', '/altas', cuerpo: ['estancia_esperada_id' => 'x'])['estado'] === 422);
check('ruta inexistente → 404; método equivocado → 405', http(hospitalFalso(), 'POST', '/camas')['estado'] === 404
    && http(hospitalFalso(), 'GET', '/traslados')['estado'] === 405);
$h = hospitalFalso();
$h->fallarEn = 'escribir';
$r = http($h, 'POST', '/altas', cuerpo: ['estancia_esperada_id' => ESTANCIA]);
check('HOSPITAL no confirma → 503 con Retry-After (fallo confirmado, reintento seguro)', $r['estado'] === 503 && $r['cabeceras'] === ['Retry-After' => '5']);

// Resultado incierto → 500; el cliente consulta la estancia en lugar de reintentar.
$h = hospitalFalso();
$h->fallarEn = 'incierto';
$r = http($h, 'POST', '/altas', cuerpo: ['estancia_esperada_id' => ESTANCIA]);
check('COMMIT sin respuesta → 500 incierto, sin Retry-After', $r['estado'] === 500 && $r['cuerpo']['resultado'] === 'incierto' && $r['cabeceras'] === []);
$h->fallarEn = null;
$c = http($h, 'GET', '/estancias/' . ESTANCIA . '/internacion');
check('GET de la estancia informa si sigue abierta', $c['estado'] === 200 && is_bool($c['cuerpo']['estancia_consultada_abierta']));
check('GET de estancia inexistente → 404; id inválido → 422', http(hospitalFalso(), 'GET', '/estancias/00000000-0000-4000-8000-00000000dead/internacion')['estado'] === 404
    && http(hospitalFalso(), 'GET', '/estancias/x/internacion')['estado'] === 422);
