<?php

declare(strict_types=1);

use MicroHis\ExpedienteMedico\Application\Clock;
use MicroHis\ExpedienteMedico\Application\ExpedientePermissions;
use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Domain\CareEpisode;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;
use MicroHis\ExpedienteMedico\Integration\PacientesDirectory;
use MicroHis\ExpedienteMedico\Persistence\HospitalRegistry;
use MicroHis\ExpedienteMedico\Persistence\InMemoryCareRelationship;
use MicroHis\ExpedienteMedico\Persistence\InMemoryMedicalRecordRepository;
use MicroHis\ExpedienteMedico\Presentation\Request;
use MicroHis\ExpedienteMedico\Presentation\Router;
use MicroHis\Gobernanza\Application\FailureReporter;
use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Persistence\PdoAuditLog;
use MicroHis\Pacientes\Domain\Patient;
use MicroHis\Pacientes\Persistence\InMemoryPatientRepository;

require_once __DIR__ . '/../app.php';

/** Datos ficticios de database/demo.sql, compartidos por los adaptadores PDO e InMemory. */
const HOSPITAL = 'hospital-demo';
const OTRO_HOSPITAL = 'hospital-norte';
const DRA_LOPEZ = '11111111-1111-4111-8111-111111111111';
const DR_PEREZ = '22222222-2222-4222-8222-222222222222';
const ENF_RUIZ = '33333333-3333-4333-8333-333333333333';
const RECEP_MORA = '44444444-4444-4444-8444-444444444444';
const ADMIN_SOL = '55555555-5555-4555-8555-555555555555';
const P1 = 'a0000000-0000-4000-8000-000000000001';
const P2 = 'a0000000-0000-4000-8000-000000000002';
const P3 = 'a0000000-0000-4000-8000-000000000003';
define('MEMBERSHIPS', require __DIR__ . '/../database/demo-memberships.php');
/** Concesión PROPUESTA (no activa en config/rbac.php): solo se usa cuando la prueba lo pide explícitamente. */
define('PROPUESTA_APERTURA', expediente_propuesta_apertura());

/** Dobles de prueba (no son integración real). */
final class SilentReporter implements FailureReporter
{
    public array $codes = [];

    public function report(string $code, array $context = []): void
    {
        $this->codes[] = $code;
    }
}

final class RecordingAuditLogger implements AuditLogger
{
    /** @var list<AuditEvent> */
    public array $events = [];

    public function __construct(public bool $down = false)
    {
    }

    public function append(AuditEvent $event): void
    {
        $this->down ? throw new AuditStorageFailure('audit_write_failed') : $this->events[] = $event;
    }

    /** @return list<string> nombres «modulo.accion» con su resultado */
    public function names(): array
    {
        return array_map(static fn (AuditEvent $e): string => $e->eventName() . ':' . $e->outcome->value, $this->events);
    }
}

final class FixedClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-10-23 12:00:00+00:00');
    }
}

$GLOBALS['checks'] = ['ok' => 0, 'fallos' => []];
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok ? $GLOBALS['checks']['ok']++ : $GLOBALS['checks']['fallos'][] = $name;
}

function date_utc(string $value): \DateTimeImmutable
{
    return new \DateTimeImmutable($value);
}

/** Base SQLite en memoria con el esquema de prueba y, si se pide, los datos ficticios. */
function hospital_pdo(bool $seed = true): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    $seed && $pdo->exec((string) file_get_contents(__DIR__ . '/../database/demo.sql'));

    return $pdo;
}

/** Bitácora real de Gobernanza (PdoAuditLog) sobre el esquema de prueba audit_log que publica ese módulo. */
function audit_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec((string) file_get_contents(__DIR__ . '/../../gobernanza-auditoria/database/audit_log.sqlite.sql'));

    return $pdo;
}

/** Los mismos datos de demo.sql en adaptadores InMemory (repositorio de Pacientes incluido). */
function memory_stores(bool $down = false, ?MedicalRecord $racer = null): HospitalStores
{
    $patients = new InMemoryPatientRepository();
    foreach ([[P1, 'P-0001', 'Ana', 'Gómez', '1980-05-14', 'female', 'active'], [P2, 'P-0002', 'Luis', 'Pérez', '1992-11-03', 'male', 'active'],
        [P3, 'P-0003', 'Marta', 'Ríos', '1941-02-20', 'female', 'deceased']] as [$id, $code, $given, $family, $birth, $sex, $status]) {
        $patients->insert(new Patient($id, 'g-' . $code, $code, $given, $family, $birth, $sex, null, $status));
    }
    $episodes = [
        new CareEpisode('ep-1', P1, 'outpatient', 'closed', date_utc('2026-03-02 09:00:00+00:00'), date_utc('2026-03-02 10:00:00+00:00'), 'Control de presión arterial', 'CONS-EXT'),
        new CareEpisode('ep-2', P1, 'emergency', 'closed', date_utc('2026-07-14 22:10:00+00:00'), date_utc('2026-07-15 03:30:00+00:00'), 'Dolor torácico', 'EMERG'),
        new CareEpisode('ep-3', P1, 'inpatient', 'open', date_utc('2026-10-20 08:00:00+00:00'), null, 'Neumonía en observación', 'MED-INT'),
        new CareEpisode('ep-4', P2, 'outpatient', 'open', date_utc('2026-10-22 14:00:00+00:00'), null, 'Primera consulta', 'CONS-EXT'),
    ];
    $records = new InMemoryMedicalRecordRepository($episodes, $down, $racer);
    $records->records[P1] = new MedicalRecord('e0000000-0000-4000-8000-000000000001', P1, 'EXP-P-0001', date_utc('2026-03-02 09:00:00+00:00'));

    return new HospitalStores($records, new PacientesDirectory($patients), new InMemoryCareRelationship([DRA_LOPEZ => [P1, P2]]));
}

/**
 * Arma el micro-HIS completo con RBAC real (matriz de config/rbac.php) y Gobernanza real (RecordAuditEvent).
 * $adapter: 'pdo' | 'memoria'. $proposed: agrega la concesión propuesta de apertura. $auditDown: bitácora caída.
 *
 * @return array{router: Router, audit: RecordingAuditLogger, stores: HospitalStores}
 */
function make_app(string $adapter = 'memoria', bool $proposed = false, bool $auditDown = false, bool $storeDown = false): array
{
    $stores = $adapter === 'pdo' ? expediente_pdo_stores(hospital_pdo()) : memory_stores($storeDown);
    if ($adapter === 'pdo' && $storeDown) {
        $pdo = hospital_pdo();
        $pdo->exec('DROP TABLE medical_records');
        $stores = expediente_pdo_stores($pdo);
    }
    $audit = new RecordingAuditLogger($auditDown);
    $router = expediente_router(
        expediente_rbac(MEMBERSHIPS, $proposed ? PROPUESTA_APERTURA : []),
        new HospitalRegistry([HOSPITAL => static fn (): HospitalStores => $stores]),
        expediente_audit($audit, new SilentReporter()),
        new FixedClock(),
    );

    return ['router' => $router, 'audit' => $audit, 'stores' => $stores];
}

/** Pasa la petición por el router (como public/index.php) y decodifica la vista JSON. */
function call(Router $router, string $method, string $patientCode, ?string $actor = DRA_LOPEZ, ?string $hospital = HOSPITAL): array
{
    $headers = array_filter(['X-User-ID' => $actor, 'X-Tenant-ID' => $hospital], static fn ($v): bool => $v !== null);
    $response = $router->dispatch(new Request($method, '/patients/' . rawurlencode($patientCode) . '/medical-record', $headers));

    return ['status' => $response['status'], 'headers' => $response['headers'], 'body' => json_decode($response['body'], true), 'raw' => $response['body']];
}

function code(array $response): string
{
    return $response['status'] . ' ' . ($response['body']['code'] ?? '');
}

/** Para comprobar que la auditoría real de Gobernanza también funciona con su adaptador PDO. */
function pdo_audit_logger(PDO $pdo): PdoAuditLog
{
    return new PdoAuditLog($pdo);
}
