<?php

declare(strict_types=1);

use MicroHis\Medicos\Application\DoctorProfileStore;
use MicroHis\Medicos\Application\DoctorSpecialtyStore;
use MicroHis\Medicos\Application\QueryAvailability;
use MicroHis\Medicos\Domain\DoctorRuleViolation;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyAssignmentPolicy;
use MicroHis\Medicos\Domain\SpecialtyLink;
use MicroHis\Medicos\Persistence\ConfigScheduleProvider;
use MicroHis\Medicos\Persistence\InMemoryAppointmentReader;
use MicroHis\Medicos\Persistence\InMemoryDoctorRepository;
use MicroHis\Medicos\Persistence\PdoDoctorStore;
use MicroHis\Medicos\Presentation\DoctorController;
use MicroHis\Medicos\Presentation\Request;
use MicroHis\Medicos\Presentation\Router;

require __DIR__ . '/../app.php';

/** Datos ficticios comunes a los dos adaptadores: Ana y Beto con NEURO principal; Ana atiende los lunes de 08:00 a 09:30. */
const SPECIALTIES = ['CARD' => ['sp-card', 'active'], 'NEURO' => ['sp-neuro', 'active'], 'PED' => ['sp-ped', 'inactive']];
const DOCTORS = ['doc-ana' => ['u-a', 'COL-9'], 'doc-beto' => ['u-b', 'COL-8']];
const ALTA = ['local_user_ref_id' => 'u-ana', 'license_number' => 'COL-1', 'specialty_code' => 'CARD', 'valid_from' => '2026-01-01'];

function schedule(): ConfigScheduleProvider
{
    return new ConfigScheduleProvider(['doc-ana' => [1 => [['08:00', '09:30']]]]);
}

/** Adaptador PDO sobre SQLite en memoria. Devuelve router, repositorio y eventos auditados. */
function pdoApp(bool $storageDown = false): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    foreach (SPECIALTIES as $code => [$id, $status]) {
        $pdo->prepare('INSERT INTO specialties VALUES (?, ?, ?)')->execute([$id, $code, $status]);
    }
    foreach (DOCTORS as $id => [$user, $license]) {
        $pdo->prepare("INSERT INTO doctor_profiles VALUES (?, ?, ?, 'active', '2026-01-01', NULL)")->execute([$id, $user, $license]);
        $pdo->prepare("INSERT INTO doctor_specialties VALUES (?, 'sp-neuro', 1, '2026-01-01', NULL, 'active')")->execute([$id]);
    }
    $pdo->exec("INSERT INTO appointments VALUES ('doc-ana', '2026-10-19 08:30:00', 'confirmed'), ('doc-ana', '2026-10-19 09:00:00', 'cancelled')");
    $storageDown && $pdo->exec('DROP TABLE local_access_audits');
    $store = new PdoDoctorStore($pdo);
    return [medicos_router($store, $store, schedule()), $store, fn (): array => $pdo->query('SELECT event FROM local_access_audits')->fetchAll(PDO::FETCH_COLUMN)];
}

/** Adaptador InMemory con los mismos datos; Citas se reemplaza por un doble con la cita confirmada de las 08:30. */
function memoryApp(bool $storageDown = false): array
{
    $profiles = $links = [];
    foreach (DOCTORS as $id => [$user, $license]) {
        $profiles[$id] = ['user' => $user, 'license' => $license, 'status' => 'active', 'period' => new Period('2026-01-01')];
        $links[$id] = [new SpecialtyLink('sp-neuro', true, new Period('2026-01-01'))];
    }
    $repository = new InMemoryDoctorRepository(SPECIALTIES, $profiles, $links, $storageDown);
    $appointments = new InMemoryAppointmentReader(['doc-ana' => ['2026-10-19' => ['08:30']]]);
    return [medicos_router($repository, $appointments, schedule()), $repository, fn (): array => array_column($repository->audits, 'event')];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

/** Pasa la petición por el router (como lo haría public/index.php) y decodifica la vista JSON. */
function call(Router $router, string $method, string $path, array $body = [], array $query = []): array
{
    $response = $router->dispatch(new Request($method, $path, ['X-User-ID' => 'admin1'], $query, $body));
    return ['status' => $response['status'], 'type' => $response['headers']['Content-Type'], 'body' => json_decode($response['body'], true)];
}

$status = static fn (array $r): string => $r['status'] . ' ' . ($r['body']['code'] ?? '');

foreach (['pdo' => 'pdoApp', 'memoria' => 'memoryApp'] as $adapter => $make) {
    $t = static fn (string $label): string => "[$adapter] $label";
    [$router, $repository, $audits] = $make();

    $r = call($router, 'POST', '/doctors', ALTA);
    $id = $r['body']['doctor_profile_id'] ?? '';
    check($t('camino feliz: POST /doctors -> 201 JSON con CARD principal y auditoría'), $r['status'] === 201 && str_starts_with($r['type'], 'application/json')
        && $repository->specialtiesOf($id)[1][0]->isPrimary && $audits() === ['medico_registrado']);
    check($t('camino feliz: POST /doctors/{id}/specialties NEURO secundaria -> 201'), call($router, 'POST', "/doctors/$id/specialties", ['specialty_code' => 'NEURO', 'valid_from' => '2026-02-01'])['status'] === 201);
    $porMedico = array_column(call($router, 'GET', '/availability', [], ['specialty' => 'NEURO', 'date' => '2026-10-19'])['body']['doctors'], null, 'doctor_id');
    check($t('camino feliz: disponibilidad del lunes -> Ana libre 08:00 y 09:00 (08:30 confirmada)'), $porMedico['doc-ana']['slots'] === ['08:00', '09:00']);
    check($t('RF-MED-07: médico sin horario aparece con SCHEDULE_NOT_CONFIGURED'), $porMedico['doc-beto']['status'] === 'SCHEDULE_NOT_CONFIGURED');
    check($t('RF-MED-06: especialidad sin médicos -> 200 NO_DOCTORS_FOR_SPECIALTY'), call($router, 'GET', '/availability', [], ['specialty' => 'CARD', 'date' => '2025-12-01'])['body']['code'] === 'NO_DOCTORS_FOR_SPECIALTY');

    check($t('regla de dominio: misma especialidad en periodo solapado -> 409'), $status(call($router, 'POST', "/doctors/$id/specialties", ['specialty_code' => 'NEURO', 'valid_from' => '2026-03-01'])) === '409 SPECIALTY_ALREADY_ASSOCIATED');
    check($t('regla de dominio: otra principal en el mismo periodo -> 409'), $status(call($router, 'POST', '/doctors/doc-beto/specialties', ['specialty_code' => 'CARD', 'is_primary' => true, 'valid_from' => '2026-03-01'])) === '409 PRIMARY_SPECIALTY_CONFLICT');
    check($t('regla de dominio: vigencia fuera del perfil -> 422'), $status(call($router, 'POST', "/doctors/$id/specialties", ['specialty_code' => 'CARD', 'valid_from' => '2025-06-01'])) === '422 PERIOD_OUTSIDE_PROFILE');
    check($t('regla de dominio: especialidad inactiva -> 422'), $status(call($router, 'POST', '/doctors', ['specialty_code' => 'PED'] + ALTA)) === '422 SPECIALTY_INACTIVE');
    check($t('regla de dominio: licencia repetida -> 409'), $status(call($router, 'POST', '/doctors', ALTA)) === '409 LICENSE_ALREADY_REGISTERED');
    check($t('regla de dominio: médico inexistente -> 404'), $status(call($router, 'POST', '/doctors/doc-nadie/specialties', ['specialty_code' => 'CARD', 'valid_from' => '2026-03-01'])) === '404 DOCTOR_NOT_FOUND');
    check($t('validación: fecha inválida -> 422'), $status(call($router, 'GET', '/availability', [], ['specialty' => 'NEURO', 'date' => '2026-02-30'])) === '422 INVALID_DATE_RANGE'
        && $status(call($router, 'GET', '/availability', [], ['specialty' => 'NEURO', 'date' => '19/10/2026'])) === '422 INVALID_DATE_RANGE');
    check($t('validación: campo no escalar -> 422 sin llegar al repositorio'), $status(call($router, 'POST', '/doctors', ['license_number' => ['COL-2']] + ALTA)) === '422 PAYLOAD_INVALID');
    check($t('ruta desconocida -> 404 ROUTE_NOT_FOUND'), $status(call($router, 'DELETE', '/doctors')) === '404 ROUTE_NOT_FOUND');

    [$router, $repository] = $make(true);
    check($t('error de persistencia: base caída -> 500 y sin perfil a medias'), $status(call($router, 'POST', '/doctors', ALTA)) === '500 DOCTOR_PERSISTENCE_ERROR' && !$repository->licenseExists('COL-1'));
}

try {
    (new SpecialtyAssignmentPolicy())->assertCanAssociate(new Period('2026-01-01'), [new SpecialtyLink('x', true, new Period('2026-01-01'))], new SpecialtyLink('y', true, new Period('2026-05-01', '2026-06-01')));
    check('regla de dominio pura (sin BD): la política rechaza dos principales', false);
} catch (DoctorRuleViolation $e) {
    check('regla de dominio pura (sin BD): la política rechaza dos principales', $e->getMessage() === 'PRIMARY_SPECIALTY_CONFLICT');
}
$tipos = static fn (string $class): array => array_map(static fn (ReflectionParameter $p): string => (string) $p->getType(), (new ReflectionMethod($class, '__construct'))->getParameters());
check('ISP: la disponibilidad no depende de puertos de escritura', !array_intersect($tipos(QueryAvailability::class), [DoctorProfileStore::class, DoctorSpecialtyStore::class]));
$controller = (string) file_get_contents((new ReflectionClass(DoctorController::class))->getFileName());
check('MVC: el controlador solo recibe casos de uso y la vista, sin SQL ni PDO', array_filter($tipos(DoctorController::class), static fn (string $t): bool => !str_contains($t, '\\Application\\') && !str_ends_with($t, '\\JsonView')) === []
    && preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|PDO)\b/', $controller) === 0);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
