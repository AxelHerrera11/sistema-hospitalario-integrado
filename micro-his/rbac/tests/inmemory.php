<?php

declare(strict_types=1);

use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\PermissionProvider;
use MicroHis\Rbac\Domain\RoleResolver;
use MicroHis\Rbac\Persistence\InMemoryRbacStore;
use MicroHis\Rbac\Persistence\PdoRbacStore;

require __DIR__ . '/../bootstrap.php';

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

function sameSet(array $a, array $b): bool
{
    sort($a);
    sort($b);

    return $a === $b;
}

$store = new InMemoryRbacStore([
    'AdminHospital' => ['rbac.asignar', 'prescripciones.ver'],
    'Medico' => ['prescripciones.crear'],
]);

check('contratos: implementa PermissionProvider, DenialRecorder y RoleResolver', $store instanceof PermissionProvider && $store instanceof DenialRecorder && $store instanceof RoleResolver);

check('permisos: rol conocido -> sus permisos', sameSet($store->permissionsFor('AdminHospital'), ['rbac.asignar', 'prescripciones.ver']));
check('permisos: rol desconocido -> lista vacía', $store->permissionsFor('Inexistente') === []);
check('permisos: código vacío -> lista vacía (sin excepción)', $store->permissionsFor('') === []);

$store->grant('med1', 'H1', 'Medico');
check('roles: usuario con rol asignado -> ese rol', $store->rolesIn('med1', 'H1') === ['Medico']);
check('roles: usuario sin asignaciones -> lista vacía', $store->rolesIn('nadie', 'H1') === []);
check('tenant: el mismo usuario en otro hospital -> lista vacía', $store->rolesIn('med1', 'H2') === []);

$store->grant('med1', 'H1', 'AdminHospital');
check('roles: varias asignaciones en el mismo hospital -> se acumulan', $store->rolesIn('med1', 'H1') === ['Medico', 'AdminHospital']);

$store->grant('med1', 'H2', 'Medico');
check('tenant: cada hospital mantiene sus propios roles', $store->rolesIn('med1', 'H2') === ['Medico'] && $store->rolesIn('med1', 'H1') === ['Medico', 'AdminHospital']);

check('denegaciones: sin registros -> lista vacía', $store->denials() === []);
$denial = ['user_id' => 'med1', 'hospital_id' => 'H1', 'permission' => 'rbac.asignar', 'reason' => 'sin_permiso_de_rol', 'trace_id' => 'trace-abc123'];
$store->record($denial);
check('denegaciones: guarda la denegación tal cual', $store->denials() === [$denial]);

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
$pdo->exec("INSERT INTO role_permissions VALUES ('AdminHospital', 'rbac.asignar'), ('AdminHospital', 'prescripciones.ver'); INSERT INTO memberships VALUES ('med1', 'H1', 'Medico'), ('med1', 'H2', 'Medico');");
$pdoStore = new PdoRbacStore($pdo);

$memStore = new InMemoryRbacStore(['AdminHospital' => ['rbac.asignar', 'prescripciones.ver']]);
$memStore->grant('med1', 'H1', 'Medico');
$memStore->grant('med1', 'H2', 'Medico');

check('paridad PDO/InMemory: permissionsFor coincide', sameSet($memStore->permissionsFor('AdminHospital'), $pdoStore->permissionsFor('AdminHospital')));
check('paridad PDO/InMemory: rolesIn coincide por hospital', sameSet($memStore->rolesIn('med1', 'H1'), $pdoStore->rolesIn('med1', 'H1')) && sameSet($memStore->rolesIn('med1', 'H2'), $pdoStore->rolesIn('med1', 'H2')));

echo 'Todas las pruebas del adaptador InMemory pasaron.' . PHP_EOL;
