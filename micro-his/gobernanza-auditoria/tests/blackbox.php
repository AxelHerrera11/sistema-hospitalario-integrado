<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;

require __DIR__ . '/harness.php';

/**
 * Caja negra: solo se usa la interfaz pública HTTP (AuditHttpHandler::search)
 * y se comparan estado, código y cuerpo con el contrato de la Semana 5.
 * Se ejecuta contra ambos adaptadores.
 */
foreach (['InMemory' => static fn () => new InMemoryAuditLog(), 'PDO' => static fn () => pdoStore()] as $name => $factory) {
    section("Contrato HTTP GET /api/v1/audit-events — {$name}");
    $store = $factory();
    $app = compose($store);
    $rec = $app['recorder'];
    $rec->record(ADMIN, H1, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security, 'membership', 'm-1', 'trace-aaa111', null, ['role_code' => 'medico']);
    $rec->record(MED, H1, 'rbac', 'access_denied', AuditOutcome::Denied, AuditCriticality::Security, 'permission', 'auditoria.ver', 'trace-bbb222', 'permiso_faltante');
    $rec->record(MED, H2, 'rbac', 'role_assigned', AuditOutcome::Success, AuditCriticality::Security, 'membership', 'm-otro');
    [$server, $headers] = as_user(ADMIN, H1);
    $http = $app['http'];

    $r = $http->search($server, $headers, ['modulo' => 'rbac', 'desde' => '2026-10-15', 'hasta' => '2026-10-15']);
    check("TC-N-01/{$name}", 'positivo: 200 con data, meta de paginación, trace_id y no-store', $r['status'] === 200 && $r['body']['meta'] === ['page' => 1, 'per_page' => 25, 'total' => 2, 'total_pages' => 1] && $r['body']['trace_id'] === 'req-prueba-01' && $r['headers']['Cache-Control'] === 'no-store, private');
    $json = json_encode($r['body']);
    check("TC-N-02/{$name}", 'aislamiento: ningún evento del otro tenant aparece', !str_contains($json, H2) && !str_contains($json, 'm-otro'));
    check("TC-N-03/{$name}", 'respuesta sin hashes de la cadena ni campos internos', !str_contains($json, 'event_hash') && !str_contains($json, 'previous_hash'));
    $denied = $r['body']['data'][0];
    check("TC-N-04/{$name}", 'el intento denegado se distingue de lo ocurrido (outcome denied, occurred false)', $denied['outcome'] === 'denied' && $denied['occurred'] === false && $r['body']['data'][1]['occurred'] === true);

    check("TC-N-05/{$name}", 'sin usuario autenticado -> 401 AUTH_REQUIRED', $http->search(['tenant_id' => H1], $headers, [])['body']['code'] === 'AUTH_REQUIRED' && $http->search(['tenant_id' => H1], $headers, [])['status'] === 401);
    check("TC-N-06/{$name}", 'sin cabecera X-Tenant-ID -> 400 TENANT_REQUIRED', $http->search($server, ['X-Request-ID' => 'req-1'], [])['status'] === 400);
    check("TC-N-07/{$name}", 'cabecera de otro tenant -> 403 TENANT_MISMATCH (no se confía en el cliente)', $http->search($server, ['X-Tenant-ID' => H2], [])['body']['code'] === 'TENANT_MISMATCH');
    check("TC-N-08/{$name}", 'parámetro tenant_id distinto del servidor -> 403 TENANT_MISMATCH', $http->search($server, $headers, ['tenant_id' => H2])['status'] === 403);
    check("TC-N-09/{$name}", 'parámetro tenant_id igual al del servidor se tolera -> 200', $http->search($server, $headers, ['tenant_id' => H1])['status'] === 200);
    $before = $app['gate']->calls;
    $r = $http->search(...array_merge(as_user(MED, H1), [['modulo' => 'rbac']]));
    check("TC-N-10/{$name}", 'usuario sin permiso auditoria.ver -> 403 RBAC_DENIED con el trace_id de RBAC', $r['status'] === 403 && $r['body'] === ['code' => 'RBAC_DENIED', 'trace_id' => 'trace-denegado'] && $app['gate']->calls === $before + 1);
    $r = $http->search(...array_merge(as_user(MED, H1), [['desde' => 'basura']]));
    check("TC-N-11/{$name}", 'sin permiso, un filtro inválido no revela validación: 403 antes que 422', $r['status'] === 403);
    $r = $http->search($server, $headers, ['desde' => '2026-13-01', 'por_pagina' => '500', 'foo' => '1']);
    check("TC-N-12/{$name}", 'filtros inválidos -> 422 AUDIT_FILTER_INVALID con errores por parámetro', $r['status'] === 422 && $r['body']['errors'] === ['desde' => 'fecha_invalida', 'foo' => 'parametro_no_permitido', 'por_pagina' => 'fuera_de_rango']);
    $r = $http->search($server, $headers, ['por_pagina' => '1', 'pagina' => '2', 'modulo' => 'rbac', 'accion' => 'role_assigned']);
    check("TC-N-13/{$name}", 'paginación con límite: página 2 de tamaño 1', $r['status'] === 200 && $r['body']['data'] === [] && $r['body']['meta']['total'] === 1 && $r['body']['meta']['page'] === 2);
    $r = $http->search($server, $headers, ['modulo' => 'gobernanza', 'accion' => 'auditoria_consultada']);
    check("TC-N-14/{$name}", 'solo las 3 consultas exitosas previas quedan auditadas (gobernanza.auditoria_consultada, Security); las rechazadas no', $r['body']['meta']['total'] === 3 && $r['body']['data'][0]['criticality'] === 'security' && $r['body']['data'][0]['context']['filter_module'] === 'rbac');
    $r = $http->search($server, ['X-Tenant-ID' => H1, 'X-Request-ID' => "bad id\n"], []);
    check("TC-N-15/{$name}", 'X-Request-ID inseguro se reemplaza por uno generado', $r['status'] === 200 && str_starts_with($r['body']['trace_id'], 'req-') && $r['body']['trace_id'] !== "bad id\n");
}

section('Fallos de dependencias y persistencia');
$app = compose(new BrokenStore());
[$server, $headers] = as_user(ADMIN, H1);
$r = $app['http']->search($server, $headers, []);
check('TC-N-16', 'almacén caído -> 503 AUDIT_UNAVAILABLE sin detalle interno y fallo reportado', $r['status'] === 503 && $r['body'] === ['code' => 'AUDIT_UNAVAILABLE', 'trace_id' => 'req-prueba-01'] && in_array('audit_query_unavailable', $app['reporter']->codes(), true));
$memory = new InMemoryAuditLog();
$app = compose(new SplitStore($memory, new BrokenStore()));
$r = $app['http']->search($server, $headers, []);
check('TC-N-17', 'lectura sana pero no se puede auditar la consulta -> 503 (falla cerrada, sin datos)', $r['status'] === 503 && !isset($r['body']['data']));
$app = compose(new InMemoryAuditLog(), new StaticGate([], 'resolucion_no_disponible'));
$r = $app['http']->search($server, $headers, []);
check('TC-N-18', 'RBAC no disponible -> 503 RBAC_UNAVAILABLE (no 403 ni 200)', $r['status'] === 503 && $r['body']['code'] === 'RBAC_UNAVAILABLE');
$app = compose(new InMemoryAuditLog());
$r = $app['http']->search(['user_id' => ADMIN], $headers, []);
check('TC-N-19', 'sin tenant resuelto por el servidor -> 503 TENANT_CONTEXT_UNAVAILABLE', $r['status'] === 503 && $r['body']['code'] === 'TENANT_CONTEXT_UNAVAILABLE');

summary('blackbox');
