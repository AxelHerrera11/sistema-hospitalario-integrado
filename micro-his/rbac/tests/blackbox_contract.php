<?php

declare(strict_types=1);

use MicroHis\Rbac\Application\AssignRole;
use MicroHis\Rbac\Domain\AssignerPrivilegeRule;
use MicroHis\Rbac\Domain\Authorizer;
use MicroHis\Rbac\Domain\RoleGrantsPermission;
use MicroHis\Rbac\Persistence\PdoRbacStore;
use MicroHis\Rbac\Presentation\RbacHttpHandler;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/harness.php';

/** Monta la pila completa sobre SQLite en memoria: solo se observan entradas, estados y cuerpos. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO role_permissions VALUES ('AdminHospital', 'rbac.asignar'); INSERT INTO memberships VALUES ('admin1', 'H1', 'AdminHospital');");
    $store = new PdoRbacStore($pdo);
    $authorizer = new Authorizer($store, $store);
    $authorizer->addRule(new RoleGrantsPermission());
    $authorizer->addRule(new AssignerPrivilegeRule());

    return [$pdo, new RbacHttpHandler(new AssignRole($store, $authorizer))];
}

$admin = ['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'];

// Respuesta autorizada: 201 con claves exactas y datos reflejados.
[$pdo, $http] = app();
$r = $http->assignRole($admin, ['user_id' => 'enf1', 'role_code' => 'Enfermeria']);
check('201: estado y claves exactas del cuerpo', $r['status'] === 201 && array_keys($r['body']) === ['user_id', 'hospital_id', 'role_code']);
check('201: refleja hospital del tenant y rol asignado', $r['body'] === ['user_id' => 'enf1', 'hospital_id' => 'H1', 'role_code' => 'Enfermeria']);

// Normalización de entradas: espacios en cabeceras y cuerpo.
$r = $http->assignRole(['X-User-ID' => ' admin1 ', 'X-Tenant-ID' => ' H1 '], ['user_id' => ' enf2 ', 'role_code' => ' Medico ']);
check('entradas con espacios -> se recortan y autorizan (201)', $r['status'] === 201 && $r['body']['user_id'] === 'enf2' && $r['body']['role_code'] === 'Medico');

// Respuesta denegada: 403 con code/message/trace_id y auditoría enlazada por trace_id.
[$pdo, $http] = app();
$r = $http->assignRole($admin, ['user_id' => 'enf3', 'role_code' => 'SuperAdmin']);
check('403: claves exactas code/message/trace_id', $r['status'] === 403 && array_keys($r['body']) === ['code', 'message', 'trace_id'] && $r['body']['code'] === 'RBAC_DENIED');
$den = $pdo->query("SELECT detail, trace_id FROM audit_log WHERE event = 'acceso_denegado'")->fetchAll(PDO::FETCH_ASSOC);
check('403: audita acceso_denegado con trace_id y motivo', $den[0]['trace_id'] === $r['body']['trace_id'] && str_contains($den[0]['detail'], 'rol_supera_privilegio'));

// Entradas inválidas: siempre 422 con solo el código, sin TypeError ni escrituras.
check('422: cabeceras ausentes -> payload_invalido', $http->assignRole([], ['user_id' => 'enf4', 'role_code' => 'Medico']) === ['status' => 422, 'body' => ['code' => 'payload_invalido']]);
check('422: tipos no string (int/bool/null) -> payload_invalido', $http->assignRole($admin, ['user_id' => 7, 'role_code' => 'Medico']) === ['status' => 422, 'body' => ['code' => 'payload_invalido']] && $http->assignRole($admin, ['user_id' => true, 'role_code' => 'Medico']) === ['status' => 422, 'body' => ['code' => 'payload_invalido']] && $http->assignRole($admin, ['user_id' => null, 'role_code' => 'Medico']) === ['status' => 422, 'body' => ['code' => 'payload_invalido']]);
check('422: user_id en blanco -> payload_invalido', $http->assignRole($admin, ['user_id' => '   ', 'role_code' => 'Medico']) === ['status' => 422, 'body' => ['code' => 'payload_invalido']]);
check('422: rol inexistente (dominio) -> payload_invalido sin trace_id', $http->assignRole($admin, ['user_id' => 'enf5', 'role_code' => 'Fantasma']) === ['status' => 422, 'body' => ['code' => 'payload_invalido']]);

// Conflicto y error de persistencia.
[$pdo, $http] = app();
$http->assignRole($admin, ['user_id' => 'enf6', 'role_code' => 'Medico']);
check('409: rol ya asignado -> solo code rol_ya_asignado', $http->assignRole($admin, ['user_id' => 'enf6', 'role_code' => 'Medico']) === ['status' => 409, 'body' => ['code' => 'rol_ya_asignado']]);

[$pdo, $http] = app();
$pdo->exec('DROP TABLE audit_log');
$r = $http->assignRole($admin, ['user_id' => 'enf7', 'role_code' => 'Enfermeria']);
check('500: auditoría caída -> RBAC_PERSISTENCE_ERROR', $r === ['status' => 500, 'body' => ['code' => 'RBAC_PERSISTENCE_ERROR']]);
check('500: transacción revertida -> sin membresía huérfana', $pdo->query("SELECT user_id FROM memberships WHERE user_id = 'enf7'")->fetchAll(PDO::FETCH_COLUMN) === []);

summary('caja negra del contrato');
