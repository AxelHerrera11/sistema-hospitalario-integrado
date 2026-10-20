<?php

declare(strict_types=1);

use MicroHis\Medicos\Application\AssociateSpecialty;
use MicroHis\Medicos\Application\QueryAvailability;
use MicroHis\Medicos\Application\RegisterDoctor;
use MicroHis\Medicos\Domain\DoctorRuleViolation;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SlotPlanner;
use MicroHis\Medicos\Domain\SpecialtyAssignmentPolicy;
use MicroHis\Medicos\Domain\SpecialtyLink;
use MicroHis\Medicos\Persistence\ConfigScheduleProvider;
use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Presentation\DoctorHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** Base de prueba: SQLite en memoria con el esquema del micro-HIS y datos ficticios. La Dra. Ana atiende los lunes de 08:00 a 09:30. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO specialties VALUES ('sp-card', 'CARD', 'active'), ('sp-neuro', 'NEURO', 'active'), ('sp-ped', 'PED', 'inactive')");
    $store = new PdoDoctorStore($pdo);
    $schedule = new ConfigScheduleProvider(['doc-ana' => [1 => [['08:00', '09:30']]]]);
    $availability = new QueryAvailability($store, $store, $schedule, $store, new SlotPlanner());
    return [$pdo, new DoctorHttpHandler(new RegisterDoctor($store, $store), new AssociateSpecialty($store, $store, new SpecialtyAssignmentPolicy()), $availability)];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

$col = static fn (PDO $pdo, string $sql): array => $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
$admin = ['X-User-ID' => 'admin1'];
$alta = ['local_user_ref_id' => 'u-ana', 'license_number' => 'COL-1', 'specialty_code' => 'CARD', 'valid_from' => '2026-01-01'];

[$pdo, $http] = app();
$r = $http->registerDoctor($admin, $alta);
$id = $r['body']['doctor_profile_id'] ?? '';
check('camino feliz: alta 201 con CARD principal y auditoría', $r['status'] === 201 && $col($pdo, 'SELECT is_primary FROM doctor_specialties') === [1] && $col($pdo, 'SELECT event FROM local_access_audits') === ['medico_registrado']);
check('camino feliz: asociar NEURO secundaria -> 201', $http->associateSpecialty($admin, $id, ['specialty_code' => 'NEURO', 'valid_from' => '2026-02-01'])['status'] === 201);
$pdo->exec("INSERT INTO doctor_profiles VALUES ('doc-ana', 'u-a', 'COL-9', 'active', '2026-01-01', NULL), ('doc-beto', 'u-b', 'COL-8', 'active', '2026-01-01', NULL)");
$pdo->exec("INSERT INTO doctor_specialties VALUES ('doc-ana', 'sp-neuro', 1, '2026-01-01', NULL, 'active'), ('doc-beto', 'sp-neuro', 1, '2026-01-01', NULL, 'active')");
$pdo->exec("INSERT INTO appointments VALUES ('doc-ana', '2026-10-19 08:30:00', 'confirmed'), ('doc-ana', '2026-10-19 09:00:00', 'cancelled')");
$body = $http->availability(['specialty' => 'NEURO', 'date' => '2026-10-19'])['body'];
$porMedico = array_column($body['doctors'], null, 'doctor_id');
check('camino feliz: disponibilidad del lunes -> Ana libre 08:00 y 09:00 (08:30 confirmada)', $porMedico['doc-ana']['slots'] === ['08:00', '09:00']);
check('RF-MED-07: médico sin horario aparece con SCHEDULE_NOT_CONFIGURED', $porMedico['doc-beto']['status'] === 'SCHEDULE_NOT_CONFIGURED');
$pdo->exec("INSERT INTO doctor_profiles VALUES ('doc-caro', 'u-c', 'COL-7', 'active', '2026-01-01', '2026-10-01')");
$pdo->exec("INSERT INTO doctor_specialties VALUES ('doc-caro', 'sp-neuro', 1, '2026-01-01', NULL, 'active')");
check('vigencia del perfil: médico con perfil vencido no aparece en la disponibilidad', !isset(array_column($http->availability(['specialty' => 'NEURO', 'date' => '2026-10-19'])['body']['doctors'], null, 'doctor_id')['doc-caro']));
check('RF-MED-06: especialidad sin médicos -> 200 NO_DOCTORS_FOR_SPECIALTY', $http->availability(['specialty' => 'CARD', 'date' => '2025-12-01'])['body']['code'] === 'NO_DOCTORS_FOR_SPECIALTY');

$status = static fn (array $r): string => $r['status'] . ' ' . ($r['body']['code'] ?? '');
check('regla de dominio: misma especialidad en periodo solapado -> 409', $status($http->associateSpecialty($admin, $id, ['specialty_code' => 'NEURO', 'valid_from' => '2026-03-01'])) === '409 SPECIALTY_ALREADY_ASSOCIATED');
check('regla de dominio: otra principal en el mismo periodo -> 409', $status($http->associateSpecialty($admin, 'doc-beto', ['specialty_code' => 'CARD', 'is_primary' => true, 'valid_from' => '2026-03-01'])) === '409 PRIMARY_SPECIALTY_CONFLICT');
check('regla de dominio: vigencia fuera del perfil -> 422', $status($http->associateSpecialty($admin, $id, ['specialty_code' => 'CARD', 'valid_from' => '2025-06-01'])) === '422 PERIOD_OUTSIDE_PROFILE');
check('regla de dominio: especialidad inactiva -> 422', $status($http->registerDoctor($admin, ['specialty_code' => 'PED'] + $alta)) === '422 SPECIALTY_INACTIVE');
check('regla de dominio: licencia repetida -> 409', $status($http->registerDoctor($admin, $alta)) === '409 LICENSE_ALREADY_REGISTERED');
check('validación: fecha inválida -> 422', $status($http->availability(['specialty' => 'NEURO', 'date' => '2026-02-30'])) === '422 INVALID_DATE_RANGE'
    && $status($http->availability(['specialty' => 'NEURO', 'date' => '19/10/2026'])) === '422 INVALID_DATE_RANGE');
$links = static fn (): array => $col($pdo, 'SELECT COUNT(*) FROM doctor_specialties');
$antes = $links();
check('validación de tipos: is_primary "false" (cadena) -> 422 sin guardar', $status($http->associateSpecialty($admin, $id, ['specialty_code' => 'CARD', 'is_primary' => 'false', 'valid_from' => '2026-11-01'])) === '422 PAYLOAD_INVALID' && $links() === $antes);
check('validación de tipos: valid_to numérico o specialty_code arreglo -> 422 sin guardar', $status($http->associateSpecialty($admin, $id, ['specialty_code' => 'CARD', 'valid_from' => '2026-11-01', 'valid_to' => 20261201])) === '422 PAYLOAD_INVALID'
    && $status($http->associateSpecialty($admin, $id, ['specialty_code' => ['CARD'], 'valid_from' => '2026-11-01'])) === '422 PAYLOAD_INVALID' && $links() === $antes);
check('validación de tipos: encabezado o campo de alta no string -> 422 sin guardar', $status($http->registerDoctor(['X-User-ID' => 7], ['license_number' => 'COL-2'] + $alta)) === '422 PAYLOAD_INVALID'
    && $status($http->registerDoctor($admin, ['license_number' => 2] + $alta)) === '422 PAYLOAD_INVALID' && $col($pdo, "SELECT COUNT(*) FROM doctor_profiles WHERE license_number IN ('COL-2', '2')") === [0]);
check('validación de tipos: consulta de disponibilidad con fecha no string -> 422', $status($http->availability(['specialty' => 'NEURO', 'date' => ['2026-10-19']])) === '422 PAYLOAD_INVALID');
check('is_primary booleano: false se guarda como secundaria (0)', $http->associateSpecialty($admin, 'doc-beto', ['specialty_code' => 'CARD', 'is_primary' => false, 'valid_from' => '2026-11-01'])['body']['is_primary'] === false && $col($pdo, "SELECT is_primary FROM doctor_specialties WHERE doctor_profile_id = 'doc-beto' AND specialty_id = 'sp-card'") === [0]);
try {
    (new SpecialtyAssignmentPolicy())->assertCanAssociate(new Period('2026-01-01'), [new SpecialtyLink('x', true, new Period('2026-01-01'))], new SpecialtyLink('y', true, new Period('2026-05-01', '2026-06-01')));
    check('regla de dominio pura (sin BD): la política rechaza dos principales', false);
} catch (DoctorRuleViolation $e) {
    check('regla de dominio pura (sin BD): la política rechaza dos principales', $e->getMessage() === 'PRIMARY_SPECIALTY_CONFLICT');
}

[$pdo, $http] = app();
$pdo->exec('DROP TABLE local_access_audits');
check('error de persistencia: auditoría caída -> 500 y rollback del perfil', $status($http->registerDoctor($admin, $alta)) === '500 DOCTOR_PERSISTENCE_ERROR' && $col($pdo, 'SELECT COUNT(*) FROM doctor_profiles') === [0]);
$tipos = array_map(static fn (ReflectionParameter $p): string => (string) $p->getType(), (new ReflectionMethod(QueryAvailability::class, '__construct'))->getParameters());
check('ISP: la disponibilidad no depende de puertos de escritura', !array_intersect($tipos, ['MicroHis\Medicos\Application\DoctorProfileStore', 'MicroHis\Medicos\Application\DoctorSpecialtyStore']));
echo 'Todas las pruebas pasaron.' . PHP_EOL;
