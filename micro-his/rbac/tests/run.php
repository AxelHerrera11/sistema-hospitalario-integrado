<?php

declare(strict_types=1);

use MicroHis\Rbac\Application\AssignRole;
use MicroHis\Rbac\Domain\AssignerPrivilegeRule;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Persistence\PdoRbacStore;
use MicroHis\Rbac\Presentation\RbacHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** Base de prueba: SQLite en memoria con el esquema real y datos ficticios. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO role_permissions VALUES ('AdminHospital', 'rbac.asignar'); INSERT INTO memberships VALUES ('admin1', 'H1', 'AdminHospital'), ('med1', 'H1', 'Medico');");
    $store = new PdoRbacStore($pdo);
    $authorizer = new Authorizer($store, $store);
    $authorizer->addRule(new RoleGrantsPermission());
    $authorizer->addRule(new AssignerPrivilegeRule());
    return [$pdo, new RbacHttpHandler(new AssignRole($store, $authorizer))];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

$col = static fn (PDO $pdo, string $sql): array => $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
$admin = ['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'];
[$pdo, $http] = app();
$r = $http->assignRole($admin, ['user_id' => 'enf1', 'role_code' => 'Enfermeria']);
check('camino feliz: 201, membresía y auditoría guardadas', $r['status'] === 201 && $col($pdo, 'SELECT event FROM audit_log') === ['rol_asignado']);
$r = $http->assignRole($admin, ['user_id' => 'enf1', 'role_code' => 'SuperAdmin']);
check('regla de dominio: rol que supera el privilegio -> 403 y denegación con trace_id', $r['status'] === 403 && in_array($r['body']['trace_id'], $col($pdo, 'SELECT trace_id FROM audit_log'), true));
$r = $http->assignRole(['X-User-ID' => 'med1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'enf1', 'role_code' => 'Recepcionista']);
check('regla de dominio: sin permiso rbac.asignar -> 403 RBAC_DENIED', $r['body']['code'] === 'RBAC_DENIED');
check('regla de dominio: rol ya asignado -> 409', $http->assignRole($admin, ['user_id' => 'enf1', 'role_code' => 'Enfermeria'])['status'] === 409);
check('validación: rol inexistente -> 422', $http->assignRole($admin, ['user_id' => 'enf1', 'role_code' => 'X'])['status'] === 422);
$before = count($col($pdo, 'SELECT event FROM audit_log'));
check('validación: entradas inválidas (array/objeto/espacios en user_id, headers no string) -> 422 sin TypeError', $http->assignRole($admin, ['user_id' => ['x'], 'role_code' => 'Enfermeria'])['status'] === 422 && $http->assignRole($admin, ['user_id' => new stdClass(), 'role_code' => 'Enfermeria'])['status'] === 422 && $http->assignRole($admin, ['user_id' => '   ', 'role_code' => 'Enfermeria'])['status'] === 422 && $http->assignRole(['X-User-ID' => ['a'], 'X-Tenant-ID' => 'H1'], ['user_id' => 'enf1', 'role_code' => 'Enfermeria'])['status'] === 422 && $http->assignRole(['X-User-ID' => 'admin1', 'X-Tenant-ID' => ['H1']], ['user_id' => 'enf1', 'role_code' => 'Enfermeria'])['status'] === 422);
check('validación: ninguna entrada inválida escribió membresía ni auditoría', count($col($pdo, 'SELECT event FROM audit_log')) === $before);
[$pdo, $http] = app();
$pdo->exec('DROP TABLE audit_log');
$r = $http->assignRole($admin, ['user_id' => 'enf1', 'role_code' => 'Enfermeria']);
check('error de persistencia: auditoría caída -> 500 y rollback', $r['status'] === 500 && $col($pdo, "SELECT user_id FROM memberships WHERE user_id = 'enf1'") === []);
check('falla segura: bitácora caída -> sigue denegado (403)', $http->assignRole($admin, ['user_id' => 'enf1', 'role_code' => 'SuperAdmin'])['status'] === 403);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
