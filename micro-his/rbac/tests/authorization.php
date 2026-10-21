<?php

declare(strict_types=1);

use MicroHis\Rbac\Application\AssignRole;
use MicroHis\Rbac\Application\Authorize;
use MicroHis\Rbac\Domain\AssignerPrivilegeRule;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use MicroHis\Rbac\Persistence\PdoRbacStore;
use MicroHis\Rbac\Presentation\RbacHttpHandler;

require __DIR__ . '/../bootstrap.php';

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

function authorizerFor(PermissionProvider $provider, DenialRecorder $recorder): Authorizer
{
    $authorizer = new Authorizer($provider, $recorder);
    $authorizer->addRule(new RoleGrantsPermission());

    return $authorizer;
}

$store = new InMemoryRbacStore([
    'AdminHospital' => ['rbac.asignar'],
    'Medico' => ['prescripciones.crear'],
]);
$store->grant('admin1', 'H1', 'AdminHospital');
$store->grant('med1', 'H1', 'Medico');
$authorize = new Authorize($store, $store, $store, new RoleGrantsPermission());

$d = $authorize->execute('med1', 'H1', 'prescripciones.crear');
check('permitido: rol con el permiso -> allowed true y trace_id', $d['allowed'] === true && str_starts_with((string) $d['trace_id'], 'trace-'));

$d = $authorize->execute('admin1', 'H1', 'prescripciones.crear');
check('denegado: rol sin el permiso -> allowed false, motivo y trace_id', $d['allowed'] === false && $d['reason'] === 'permiso_faltante' && str_starts_with((string) $d['trace_id'], 'trace-'));

$d = $authorize->execute('nadie', 'H1', 'prescripciones.crear');
check('no autenticado: usuario sin roles -> allowed false', $d['allowed'] === false);

$d = $authorize->execute('admin1', 'H1', 'rbac.inexistente');
check('permiso desconocido -> allowed false', $d['allowed'] === false);

check('la denegación queda registrada en el DenialRecorder', count($store->denials()) >= 3);

$failing = new class implements PermissionProvider, DenialRecorder {
    public function permissionsFor(string $roleCode): array
    {
        return [];
    }

    public function record(array $denial): void
    {
        throw new RuntimeException('bitacora caida');
    }
};
$trace = authorizerFor($failing, $failing)->authorize(['user_id' => 'med1', 'hospital_id' => 'H1', 'permission' => 'prescripciones.crear', 'roles' => ['Medico']]);
check('falla segura: la bitácora falla -> sigue denegado con trace_id', is_string($trace) && str_starts_with($trace, 'trace-'));

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
$pdo->exec("INSERT INTO role_permissions VALUES ('AdminHospital', 'rbac.asignar'); INSERT INTO role_permissions VALUES ('AdminHospital', 'rbac.autorizar'); INSERT INTO memberships VALUES ('admin1', 'H1', 'AdminHospital');");
$pdoStore = new PdoRbacStore($pdo);
$pdoAuthorizer = new Authorizer($pdoStore, $pdoStore);
$pdoAuthorizer->addRule(new RoleGrantsPermission());
$pdoAuthorizer->addRule(new AssignerPrivilegeRule());
$http = new RbacHttpHandler(new AssignRole($pdoStore, $pdoAuthorizer), new Authorize($pdoStore, $pdoStore, $pdoStore, new RoleGrantsPermission(), new AssignerPrivilegeRule()), 'rbac.autorizar');

$r = $http->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['action' => 'rbac.asignar']);
check('handler: acción permitida -> 200 allowed true', $r['status'] === 200 && $r['body']['allowed'] === true);
$r = $http->authorize(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['action' => 'rbac.eliminar']);
check('handler: acción no cubierta -> 200 allowed false', $r['status'] === 200 && $r['body']['allowed'] === false);
$r = $http->authorize(['X-User-ID' => ['x']], ['action' => 'rbac.asignar']);
check('handler: entradas inválidas -> 422 sin TypeError', $r['status'] === 422);

echo 'Todas las pruebas de autorización pasaron.' . PHP_EOL;
