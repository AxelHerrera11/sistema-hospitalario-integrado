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

/** AdminHospital y Recepcionista pueden asignar; el nivel del asignador acota a quién puede otorgar. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO role_permissions VALUES ('AdminHospital', 'rbac.asignar'), ('Recepcionista', 'rbac.asignar');
                INSERT INTO memberships VALUES ('admin1', 'H1', 'AdminHospital'), ('recep1', 'H1', 'Recepcionista');");
    $store = new PdoRbacStore($pdo);
    $authorizer = new Authorizer($store, $store);
    $authorizer->addRule(new RoleGrantsPermission());
    $authorizer->addRule(new AssignerPrivilegeRule());

    return [$pdo, $store, new RbacHttpHandler(new AssignRole($store, $authorizer))];
}

// Aislamiento entre hospitales: el mismo usuario no hereda roles de otro tenant.
[$pdo, $store, $http] = app();
check('aislamiento: sin membresía en H2 -> roles vacíos', $store->rolesIn('admin1', 'H2') === []);
$r = $http->assignRole(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H2'], ['user_id' => 'enf1', 'role_code' => 'Medico']);
check('aislamiento: actuar en H2 sin rol -> 403', $r['status'] === 403);
check('aislamiento: no se crea membresía cruzada en H2', $store->rolesIn('enf1', 'H2') === []);
check('aislamiento: la membresía propia en H1 se conserva', $store->rolesIn('admin1', 'H1') === ['AdminHospital']);
$den = $pdo->query("SELECT hospital_id FROM audit_log WHERE event = 'acceso_denegado'")->fetchAll(PDO::FETCH_COLUMN);
check('aislamiento: la denegación se audita con el tenant solicitado (H2)', $den === ['H2']);

// Escalamiento de privilegios: el rol otorgado no puede superar el nivel del asignador.
[$pdo, $store, $http] = app();
check('escalamiento: AdminHospital(80) no puede otorgar SuperAdmin(100) -> 403', $http->assignRole(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'enf2', 'role_code' => 'SuperAdmin'])['status'] === 403);
check('escalamiento: no se persiste la membresía elevada', $store->rolesIn('enf2', 'H1') === []);
check('escalamiento: admin no puede auto-elevarse -> 403', $http->assignRole(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'admin1', 'role_code' => 'SuperAdmin'])['status'] === 403);
check('escalamiento: Recepcionista(20) no puede otorgar Medico(50) -> 403', $http->assignRole(['X-User-ID' => 'recep1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'enf3', 'role_code' => 'Medico'])['status'] === 403);
$motivos = $pdo->query("SELECT detail FROM audit_log WHERE event = 'acceso_denegado'")->fetchAll(PDO::FETCH_COLUMN);
check('escalamiento: se audita el motivo rol_supera_privilegio', str_contains(implode('|', $motivos), 'rol_supera_privilegio'));

// Permisos autorizados: asignaciones dentro del nivel del asignador.
check('autorizado: AdminHospital otorga Medico(50) -> 201', $http->assignRole(['X-User-ID' => 'admin1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'enf4', 'role_code' => 'Medico'])['status'] === 201);
check('autorizado: Recepcionista otorga su propio nivel(20) -> 201', $http->assignRole(['X-User-ID' => 'recep1', 'X-Tenant-ID' => 'H1'], ['user_id' => 'enf5', 'role_code' => 'Recepcionista'])['status'] === 201);
check('autorizado: la membresía otorgada queda en el hospital correcto', $store->rolesIn('enf4', 'H1') === ['Medico'] && $store->rolesIn('enf5', 'H1') === ['Recepcionista']);

// Permisos denegados: sin el permiso rbac.asignar, aunque el nivel alcanzaría.
[$pdo, $store, $http] = app();
check('denegado: Medico sin rbac.asignar -> 403', $http->assignRole(['X-User-ID' => 'med_sin_permiso', 'X-Tenant-ID' => 'H1'], ['user_id' => 'enf6', 'role_code' => 'Enfermeria'])['status'] === 403);
check('denegado: sin permiso no se crea membresía', $store->rolesIn('enf6', 'H1') === []);

summary('aislamiento y escalamiento');
