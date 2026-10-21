<?php

declare(strict_types=1);

use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\GobernanzaModule;
use MicroHis\Gobernanza\Integration\RbacAccessGate;
use MicroHis\Gobernanza\Integration\RbacDenialRecorder;
use MicroHis\Gobernanza\Persistence\InMemoryAuditLog;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Domain\RoleResolver;

require __DIR__ . '/harness.php';

/**
 * Integración con el RBAC real (micro-his/rbac). Por defecto usa la copia del
 * repositorio (../../rbac); RBAC_DIR permite apuntar a otra versión, por
 * ejemplo la de develop exportada con:
 *   git archive origin/develop micro-his/rbac | tar -x -C <dir>
 *   RBAC_DIR=<dir>/micro-his/rbac php tests/rbac_integration.php
 */
$rbacDir = getenv('RBAC_DIR') ?: __DIR__ . '/../../rbac';
if (!is_file($rbacDir . '/bootstrap.php')) {
    echo "OMITIDA: no se encontró micro-his/rbac en {$rbacDir}" . PHP_EOL;
    exit(0);
}
require_once $rbacDir . '/bootstrap.php';
echo "RBAC usado: " . realpath($rbacDir) . PHP_EOL;

$permissions = new class implements PermissionProvider {
    public function permissionsFor(string $roleCode): array
    {
        return ['superadmin' => ['auditoria.ver'], 'medico' => ['prescripciones.ver']][$roleCode] ?? [];
    }
};
$memory = new InMemoryAuditLog();
$reporter = new RecordingReporter();
$recorder = new RecordAuditEvent($memory, new FixedClock(), $reporter);
$denials = new RbacDenialRecorder($recorder);

section('DenialRecorder de RBAC -> AuditLogger (Authorizer)');
$authorizer = new Authorizer($permissions, $denials);
$authorizer->addRule(new RoleGrantsPermission());
$trace = $authorizer->authorize(['user_id' => MED, 'hospital_id' => H1, 'permission' => 'auditoria.ver', 'roles' => ['medico']]);
$stored = $memory->search(H1, new AuditFilter(module: 'rbac', action: 'access_denied'))->events;
check('TC-I-01', 'la denegación queda como rbac.access_denied, outcome denied, con el mismo trace_id', is_string($trace) && count($stored) === 1 && $stored[0]->correlationId === $trace && $stored[0]->outcome === AuditOutcome::Denied);
check('TC-I-02', 'recurso = permiso solicitado y motivo = código de RBAC', $stored[0]->resourceType === 'permission' && $stored[0]->resourceId === 'auditoria.ver' && $stored[0]->reason === 'sin_permiso_de_rol');
$broken = new Authorizer($permissions, new RbacDenialRecorder(new RecordAuditEvent(new BrokenStore(), new FixedClock(), $reporter)));
$broken->addRule(new RoleGrantsPermission());
check('TC-I-03', 'bitácora caída: la denegación se mantiene (falla segura) y el fallo se reporta', is_string($broken->authorize(['user_id' => MED, 'hospital_id' => H1, 'permission' => 'auditoria.ver', 'roles' => ['medico']])) && in_array('audit_record_failed', $reporter->codes(), true));

$silent = new RecordingReporter();
$noUser = new Authorizer($permissions, new RbacDenialRecorder(new RecordAuditEvent(new InMemoryAuditLog(), new FixedClock(), $silent)));
$noUser->addRule(new RoleGrantsPermission());
check('TC-I-07', 'regresión H-GOB-04: denegación sin user_id -> sigue denegada y queda reportada (audit_event_invalid)', is_string($noUser->authorize(['user_id' => '', 'hospital_id' => H1, 'permission' => 'auditoria.ver', 'roles' => []])) && $silent->codes() === ['audit_event_invalid']);

section('AccessGate sobre Authorize (develop)');
// El autoload de RBAC no comprueba que el archivo exista: se verifica antes de class_exists().
if (!is_file($rbacDir . '/src/Application/Authorize.php')) {
    echo 'OMITIDA: esta copia de micro-his/rbac no tiene Application\\Authorize (existe en origin/develop). Ejecute con RBAC_DIR apuntando a develop.' . PHP_EOL;
    summary('rbac_integration');
}
$roles = new class implements RoleResolver {
    public function rolesIn(string $userId, string $hospitalId): array
    {
        return [ADMIN . '@' . H1 => ['superadmin'], MED . '@' . H1 => ['medico']]["{$userId}@{$hospitalId}"] ?? [];
    }
};
$authorize = new Authorize($roles, $permissions, $denials, new RoleGrantsPermission());
$http = GobernanzaModule::compose($memory, $memory, new RbacAccessGate($authorize), new FixedClock(), $reporter)->http;
$ok = $http->search(...array_merge(as_user(ADMIN, H1), [['modulo' => 'rbac']]));
check('TC-I-04', 'rol con auditoria.ver (configurado en la prueba) -> 200 y ve la denegación previa', $ok['status'] === 200 && $ok['body']['data'][0]['event'] === 'rbac.access_denied');
$no = $http->search(...array_merge(as_user(MED, H1), [[]]));
$last = $memory->search(H1, new AuditFilter(module: 'rbac'))->events[0];
check('TC-I-05', 'rol sin permiso -> 403 RBAC_DENIED y la denegación se audita con el trace_id devuelto', $no['status'] === 403 && $no['body']['code'] === 'RBAC_DENIED' && $last->correlationId === $no['body']['trace_id']);
$other = $http->search(...array_merge(as_user(ADMIN, H2), [[]]));
check('TC-I-06', 'superadmin de H1 sin membresía en H2 -> 403 (el permiso no cruza tenants)', $other['status'] === 403);

summary('rbac_integration');
