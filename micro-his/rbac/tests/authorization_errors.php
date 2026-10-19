<?php

declare(strict_types=1);

use MicroHis\Rbac\Application\AssignRole;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Domain\RoleResolver;
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use MicroHis\Rbac\Persistence\PdoRbacStore;
use MicroHis\Rbac\Presentation\RbacHttpHandler;

require __DIR__ . '/../bootstrap.php';

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

function isTrace(mixed $t): bool
{
    return is_string($t) && str_starts_with($t, 'trace-');
}

/** Un 503 debe exponer siempre allowed=false, code, reason y trace_id. */
function isDependency(array $body, string $code): bool
{
    return $body['allowed'] === false && $body['code'] === $code && $body['reason'] === $code && isTrace($body['trace_id']);
}

/** AssignRole exige el PdoRbacStore concreto; aqui solo se necesita para construir el handler. */
function dummyAssignRole(): AssignRole
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $store = new PdoRbacStore($pdo);
    $authorizer = new Authorizer($store, $store);
    $authorizer->addRule(new RoleGrantsPermission());

    return new AssignRole($store, $authorizer);
}

// 1. Construccion insegura imposible: el constructor exige un DenialRecorder (3er parametro no nulable).
$ctor = new ReflectionMethod(Authorize::class, '__construct');
$denials = $ctor->getParameters()[2];
check('Authorize no se puede construir sin seguimiento de auditoria', $ctor->isPublic() && $ctor->getNumberOfRequiredParameters() >= 3 && $denials->getName() === 'denials' && (string) $denials->getType() === DenialRecorder::class && !$denials->allowsNull());

$provider = new InMemoryRbacStore(['AdminHospital' => ['rbac.asignar', 'rbac.autorizar'], 'Medico' => ['prescripciones.crear']]);
$provider->grant('admin1', 'H1', 'AdminHospital');
$provider->grant('med1', 'H1', 'Medico');

// 2. RoleResolver lanza -> allowed false, resolucion_no_disponible, sin filtrar el detalle.
$badResolver = new class implements RoleResolver, PermissionProvider, DenialRecorder {
    public function rolesIn(string $u, string $h): array { throw new RuntimeException('dsn=secreto'); }
    public function permissionsFor(string $r): array { return []; }
    public function record(array $d): void {}
};
$d = (new Authorize($badResolver, $badResolver, $badResolver, new RoleGrantsPermission()))->execute('med1', 'H1', 'prescripciones.crear');
check('RoleResolver lanza -> allowed false, resolucion_no_disponible, sin filtrar detalle', $d['allowed'] === false && $d['reason'] === 'resolucion_no_disponible' && isTrace($d['trace_id']) && !str_contains((string) $d['reason'], 'secreto'));

// 3. PermissionProvider lanza -> mismo fail-safe.
$badProvider = new class implements RoleResolver, PermissionProvider, DenialRecorder {
    public function rolesIn(string $u, string $h): array { return ['Medico']; }
    public function permissionsFor(string $r): array { throw new RuntimeException('sql: SELECT'); }
    public function record(array $d): void {}
};
$d = (new Authorize($badProvider, $badProvider, $badProvider, new RoleGrantsPermission()))->execute('med1', 'H1', 'prescripciones.crear');
check('PermissionProvider lanza -> allowed false, resolucion_no_disponible', $d['allowed'] === false && $d['reason'] === 'resolucion_no_disponible');

// 4. DenialRecorder lanza -> denegacion conservada y distinguida.
$throwing = new class implements DenialRecorder {
    public function record(array $d): void { throw new RuntimeException('caida'); }
};
$d = (new Authorize($provider, $provider, $throwing, new RoleGrantsPermission()))->execute('admin1', 'H1', 'prescripciones.crear');
check('DenialRecorder lanza -> allowed false, registro_no_disponible, trace_id', $d['allowed'] === false && $d['reason'] === 'registro_no_disponible' && isTrace($d['trace_id']));

// 5. El Authorizer y el caso de uso comparten la MISMA instancia de registro (registra una sola vez).
$spy = new class implements DenialRecorder {
    public int $calls = 0;
    public function record(array $d): void { $this->calls++; }
};
$d = (new Authorize($provider, $provider, $spy, new RoleGrantsPermission()))->execute('admin1', 'H1', 'prescripciones.crear');
check('Authorizer y caso de uso comparten el mismo registro (1 sola llamada)', $d['allowed'] === false && $d['reason'] === 'permiso_faltante' && $spy->calls === 1);

// 6. Denegacion ordinaria -> allowed false, permiso_faltante (no se confunde con el fallo de dependencia).
$d = (new Authorize($provider, $provider, $provider, new RoleGrantsPermission()))->execute('admin1', 'H1', 'prescripciones.crear');
check('denegacion ordinaria -> allowed false, permiso_faltante', $d['allowed'] === false && $d['reason'] === 'permiso_faltante');

$authorize = new Authorize($provider, $provider, $provider, new RoleGrantsPermission());
$handler = new RbacHttpHandler(dummyAssignRole(), $authorize, 'rbac.autorizar');

// 7. Identidad y tenant correctos: admin1 evalua a med1 -> 200 allowed true.
$r = $handler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'med1', 'action' => 'prescripciones.crear']);
check('handler: evalua al usuario del body -> 200 allowed true', $r['status'] === 200 && $r['body']['allowed'] === true && isTrace($r['body']['trace_id']));

// 8. Operacion evaluada y denegada -> 200 allowed false permiso_faltante.
$r = $handler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'med1', 'action' => 'rbac.asignar']);
check('handler: operacion evaluada y denegada -> 200 allowed false permiso_faltante', $r['status'] === 200 && $r['body']['allowed'] === false && $r['body']['reason'] === 'permiso_faltante');

// 9. Sin autenticacion -> 401 con trace_id.
$r = $handler->authorize([], ['action' => 'x']);
check('handler: sin autenticacion -> 401 con trace_id', $r['status'] === 401 && $r['body']['code'] === 'no_autenticado' && isTrace($r['body']['trace_id']));

// 10. Entrada invalida -> 422 con trace_id.
$r = $handler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], []);
check('handler: entrada invalida -> 422 con trace_id', $r['status'] === 422 && $r['body']['code'] === 'payload_invalido' && isTrace($r['body']['trace_id']));

// 11. Hospital ausente -> 422 (no se evalua sin tenant).
$r = $handler->authorize(['X-User-ID' => 'admin1'], ['user_id' => 'med1', 'action' => 'prescripciones.crear']);
check('handler: sin hospital -> 422 payload_invalido', $r['status'] === 422 && $r['body']['code'] === 'payload_invalido' && isTrace($r['body']['trace_id']));

// Regresion: user_id presente pero invalido NO se sustituye por el solicitante.
$spy = new class implements RoleResolver, PermissionProvider, DenialRecorder {
    public int $calls = 0;
    public function rolesIn(string $u, string $h): array { $this->calls++; return []; }
    public function permissionsFor(string $r): array { $this->calls++; return []; }
    public function record(array $d): void { $this->calls++; }
};
$spyHandler = new RbacHttpHandler(dummyAssignRole(), new Authorize($spy, $spy, $spy, new RoleGrantsPermission()), 'rbac.autorizar');
$r = $spyHandler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => [], 'action' => 'prescripciones.crear']);
check('handler: user_id=[] -> 422 con trace_id y sin invocar autorizacion', $r['status'] === 422 && $r['body']['code'] === 'payload_invalido' && isTrace($r['body']['trace_id']) && $spy->calls === 0);

// user_id ausente -> fallback al solicitante (boss1 con permiso de endpoint y de accion).
$both = new InMemoryRbacStore(['Jefe' => ['rbac.autorizar', 'prescripciones.crear']]);
$both->grant('boss1', 'H1', 'Jefe');
$bothHandler = new RbacHttpHandler(dummyAssignRole(), new Authorize($both, $both, $both, new RoleGrantsPermission()), 'rbac.autorizar');
$r = $bothHandler->authorize(['X-User-ID' => 'boss1', 'X-Tenant-ID' => 'H1'], ['action' => 'prescripciones.crear']);
check('handler: user_id ausente -> fallback al solicitante', $r['status'] === 200 && $r['body']['allowed'] === true);

// user_id valido distinto del solicitante -> se evalua el del body, no el solicitante.
$r = $bothHandler->authorize(['X-User-ID' => 'boss1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'nadie', 'action' => 'prescripciones.crear']);
check('handler: user_id valido -> evalua el del body, no el solicitante', $r['status'] === 200 && $r['body']['allowed'] === false && $r['body']['reason'] === 'permiso_faltante');

// Otros invalidos (null, vacio, false, 0, array, objeto) -> 422, sin invocar autorizacion.
$invalidos = [null, '', '   ', false, 0, ['med1'], new stdClass()];
$todos422 = true;
foreach ($invalidos as $v) {
    $r = $spyHandler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => $v, 'action' => 'prescripciones.crear']);
    $todos422 = $todos422 && $r['status'] === 422 && isTrace($r['body']['trace_id']);
}
check('handler: user_id null/vacio/false/0/array/objeto -> 422 con trace_id', $todos422 && $spy->calls === 0);

// 12. Solicitante sin permiso de endpoint -> 403 (distinto de la denegacion evaluada).
$rec = new InMemoryRbacStore(['Recepcionista' => ['muestras.ver']]);
$rec->grant('rec1', 'H1', 'Recepcionista');
$recHandler = new RbacHttpHandler(dummyAssignRole(), new Authorize($rec, $rec, $rec, new RoleGrantsPermission()), 'rbac.autorizar');
$r = $recHandler->authorize(['X-User-ID' => 'rec1', 'X-Tenant-ID' => 'H1'], ['action' => 'muestras.ver']);
check('handler: solicitante sin permiso de endpoint -> 403 operacion_no_autorizada', $r['status'] === 403 && $r['body']['code'] === 'operacion_no_autorizada' && isTrace($r['body']['trace_id']));

// 13. Endpoint sin configurar -> 503 unificado (fail-closed).
$noEndpoint = new RbacHttpHandler(dummyAssignRole(), $authorize);
$r = $noEndpoint->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'med1', 'action' => 'prescripciones.crear']);
check('handler: endpoint sin configurar -> 503 endpoint_no_configurado unificado', $r['status'] === 503 && isDependency($r['body'], 'endpoint_no_configurado'));

// 14. Fallo de auditoria via handler -> 503 registro_no_disponible unificado.
$downHandler = new RbacHttpHandler(dummyAssignRole(), new Authorize($provider, $provider, $throwing, new RoleGrantsPermission()), 'rbac.autorizar');
$r = $downHandler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'admin1', 'action' => 'prescripciones.crear']);
check('handler: auditoria caida -> 503 registro_no_disponible unificado', $r['status'] === 503 && isDependency($r['body'], 'registro_no_disponible'));

// 15. Resolucion caida via handler -> 503 resolucion_no_disponible unificado.
$trHandler = new RbacHttpHandler(dummyAssignRole(), new Authorize($badResolver, $badResolver, $badResolver, new RoleGrantsPermission()), 'rbac.autorizar');
$r = $trHandler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'med1', 'action' => 'prescripciones.crear']);
check('handler: RoleResolver lanza -> 503 resolucion_no_disponible unificado', $r['status'] === 503 && isDependency($r['body'], 'resolucion_no_disponible'));

// 16. Fallo transitorio reutilizado: 1er 503 unificado y luego 200 permiso_faltante.
$flaky = new class implements DenialRecorder {
    public int $calls = 0;
    public function record(array $d): void { $this->calls++; if ($this->calls === 1) { throw new RuntimeException('transitorio'); } }
};
$reuseHandler = new RbacHttpHandler(dummyAssignRole(), new Authorize($provider, $provider, $flaky, new RoleGrantsPermission()), 'rbac.autorizar');
$r1 = $reuseHandler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'admin1', 'action' => 'prescripciones.crear']);
$r2 = $reuseHandler->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'admin1', 'action' => 'prescripciones.crear']);
check('handler: fallo transitorio -> 1er 503 unificado y luego 200 permiso_faltante', $r1['status'] === 503 && isDependency($r1['body'], 'registro_no_disponible') && $r2['status'] === 200 && $r2['body']['reason'] === 'permiso_faltante');

// 17. Cuerpo 200 con claves exactas.
$keys = array_keys($r2['body']); sort($keys);
check('handler: 200 -> claves exactas allowed/reason/trace_id', $keys === ['allowed', 'reason', 'trace_id']);

echo 'Todas las pruebas de errores de autorizacion pasaron.' . PHP_EOL;
