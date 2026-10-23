<?php

declare(strict_types=1);

/**
 * CAJA NEGRA sobre el contrato HTTP /api/v1/qa/runs (solo entradas y salidas):
 * particiones de equivalencia, valores límite, permisos/tenant y errores.
 */
require __DIR__.'/harness.php';

use MicroHis\QaCalidad\Application\RunInputParser;
use MicroHis\QaCalidad\Integration\StaticAccessGate;
use MicroHis\QaCalidad\Presentation\QaHttpHandler;

$app = compose();
$http = $app['module']->http;
$as = static fn (string $user, string $tenant = H1, ?string $header = null): array => [
    ['user_id' => $user, 'tenant_id' => $tenant],
    ['X-Tenant-ID' => $header ?? $tenant, 'X-Request-ID' => 'req-bn-0001'],
];
$call = static function (QaHttpHandler $h, string $method, string $path, array $who, ?array $body = null, string $raw = ''): array {
    return $h->handle($method, $path, $who[0], $who[1], [], $body === null ? $raw : json_encode($body));
};

section('Permisos y tenant');
$r = $call($http, 'GET', '/api/v1/qa/runs', [[], []]);
check('BN01', 'Sin identidad → 401 AUTH_REQUIRED', $r['status'] === 401 && $r['body']['code'] === 'AUTH_REQUIRED');
$r = $call($http, 'GET', '/api/v1/qa/runs', [['user_id' => LIDER, 'tenant_id' => H1], []]);
check('BN02', 'Sin X-Tenant-ID → 400 TENANT_REQUIRED', $r['status'] === 400 && $r['body']['code'] === 'TENANT_REQUIRED');
$r = $call($http, 'GET', '/api/v1/qa/runs', $as(LIDER, H1, H2));
check('BN03', 'X-Tenant-ID distinto al del token → 403 TENANT_MISMATCH', $r['status'] === 403 && $r['body']['code'] === 'TENANT_MISMATCH');
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(OBS), planBody());
check('BN04', 'Observador (solo ver) intenta planificar → 403 RBAC_DENIED', $r['status'] === 403 && $r['body']['code'] === 'RBAC_DENIED');
$r = $call($http, 'GET', '/api/v1/qa/runs', $as(OBS));
check('BN05', 'Observador puede listar → 200', $r['status'] === 200 && $r['body']['data'] === []);

section('Equivalencia: camino feliz completo');
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), planBody());
$id = $r['body']['data']['id'] ?? '';
check('BN06', 'Plan válido → 201 con status planned y 3 suites', $r['status'] === 201 && $r['body']['data']['status'] === 'planned' && count($r['body']['data']['suites']) === 3);
check('BN07', 'Respuesta no cacheable y con el mismo X-Request-ID', $r['headers']['Cache-Control'] === 'no-store, private' && $r['headers']['X-Request-ID'] === 'req-bn-0001' && $r['body']['trace_id'] === 'req-bn-0001');
$call($http, 'POST', "/api/v1/qa/runs/{$id}/transitions", $as(LIDER), ['transition' => 'start']);
$call($http, 'POST', "/api/v1/qa/runs/{$id}/results", $as(LIDER), resultBody('phpunit-unit', 120));
$r = $call($http, 'POST', "/api/v1/qa/runs/{$id}/results", $as(LIDER), resultBody('e2e-auth', 9));
check('BN08', 'Registrar resultado → 201 con passed calculado', $r['status'] === 201 && $r['body']['data']['suites'][1]['result']['passed'] === 9);
$call($http, 'POST', "/api/v1/qa/runs/{$id}/transitions", $as(LIDER), ['transition' => 'complete']);
$r = $call($http, 'POST', "/api/v1/qa/runs/{$id}/gate-decisions", $as(LIDER));
check('BN09', 'Gate de ejecución verde → 201 verdict approved', $r['status'] === 201 && $r['body']['data']['verdict'] === 'approved');
$r = $call($http, 'GET', "/api/v1/qa/runs/{$id}", $as(OBS));
check('BN10', 'Detalle incluye la última decisión del gate', $r['status'] === 200 && $r['body']['data']['gate']['verdict'] === 'approved');

section('Errores: 404, 409, 422, 400, 413');
$r = $call($http, 'GET', "/api/v1/qa/runs/{$id}", $as(LIDER, H2));
check('BN11', 'Ejecución de otro hospital → 404 (no revela que existe)', $r['status'] === 404 && $r['body']['code'] === 'QA_RUN_NOT_FOUND');
$r = $call($http, 'POST', "/api/v1/qa/runs/{$id}/results", $as(LIDER), resultBody('pint', 3));
check('BN12', 'Resultado sobre ejecución completada → 409 QA_RUN_STATE_CONFLICT', $r['status'] === 409 && $r['body']['reason'] === 'estado_invalido');
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), planBody(['environment' => 'production']));
check('BN13', 'Ambiente production → 422 ambiente_no_permitido', $r['status'] === 422 && $r['body']['reason'] === 'ambiente_no_permitido');
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), planBody(['suites' => [['name' => 'u', 'kind' => 'unit', 'required' => 'false']]]));
check('BN14', 'required como texto "false" → 422 (no se vuelve opcional en silencio)', $r['status'] === 422 && $r['body']['reason'] === 'campo_obligatorio');
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), null, '{"release_version":');
check('BN15', 'JSON mal formado → 400 INVALID_JSON', $r['status'] === 400 && $r['body']['code'] === 'INVALID_JSON');
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), null, str_repeat(' ', QaHttpHandler::MAX_BODY_BYTES + 1));
check('BN16', 'Cuerpo de 64 KiB + 1 byte → 413 PAYLOAD_TOO_LARGE', $r['status'] === 413);
$r = $call($http, 'DELETE', "/api/v1/qa/runs/{$id}", $as(LIDER));
check('BN17', 'Método no soportado → 404 ROUTE_NOT_FOUND (no hay borrado de evidencia)', $r['status'] === 404 && $r['body']['code'] === 'ROUTE_NOT_FOUND');

section('Valores límite del plan');
$many = static fn (int $n): array => array_map(static fn (int $i): array => ['name' => "s{$i}", 'kind' => $i === 0 ? 'unit' : 'e2e', 'required' => true], range(0, $n - 1));
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), planBody(['suites' => $many(RunInputParser::MAX_SUITES)]));
check('BN18', 'Exactamente 30 suites → 201', $r['status'] === 201);
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), planBody(['suites' => $many(RunInputParser::MAX_SUITES + 1)]));
check('BN19', '31 suites → 422 suites_invalidas', $r['status'] === 422 && $r['body']['reason'] === 'suites_invalidas');
$r = $call($http, 'POST', '/api/v1/qa/runs', $as(LIDER), planBody(['suites' => []]));
check('BN20', '0 suites → 422 suites_invalidas', $r['status'] === 422 && $r['body']['reason'] === 'suites_invalidas');
$r = $call($http, 'GET', '/api/v1/qa/runs', $as(LIDER));
check('BN21', 'Listado respeta el tenant: H1 ve sus 2 ejecuciones', $r['status'] === 200 && count($r['body']['data']) === 2);

section('Dependencias caídas');
$down = compose(null, new StaticAccessGate(grants(), true))['module']->http;
$r = $call($down, 'GET', '/api/v1/qa/runs', $as(LIDER));
check('BN22', 'RBAC no disponible → 503 RBAC_UNAVAILABLE (falla cerrado)', $r['status'] === 503 && $r['body']['code'] === 'RBAC_UNAVAILABLE');
$broken = compose(new BrokenRepository)['module']->http;
$r = $call($broken, 'POST', '/api/v1/qa/runs', $as(LIDER), planBody());
check('BN23', 'Almacén caído → 503 QA_STORAGE_UNAVAILABLE sin detalle técnico', $r['status'] === 503 && $r['body']['code'] === 'QA_STORAGE_UNAVAILABLE' && ! isset($r['body']['message']));

summary('blackbox');
