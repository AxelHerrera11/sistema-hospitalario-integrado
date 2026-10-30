<?php

declare(strict_types=1);

use MicroHis\ExpedienteMedico\Application\HospitalStores;
use MicroHis\ExpedienteMedico\Application\PersistenceFailure;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;
use MicroHis\ExpedienteMedico\Persistence\HospitalRegistry;
use MicroHis\ExpedienteMedico\Persistence\PdoCareRelationship;
use MicroHis\ExpedienteMedico\Persistence\PdoMedicalRecordRepository;

/**
 * Integración de los adaptadores PDO con SQLite (no PostgreSQL: ver README) y con la bitácora REAL de Gobernanza
 * (PdoAuditLog sobre su esquema de prueba audit_log). Cubre persistencia, concurrencia, reversión y aislamiento.
 */
echo PHP_EOL . '== Integración PDO + Gobernanza' . PHP_EOL;

// P-01 Vínculo asistencial: misma condición que el trigger de notas clínicas (asignación activa, sin valid_to).
$pdo = hospital_pdo();
$link = new PdoCareRelationship($pdo);
check('P-01 vínculo: López↔P-0001 sí; Pérez (asignación cerrada)↔P-0001 no; López↔P-0003 no',
    $link->hasActiveAssignment(DRA_LOPEZ, P1) && !$link->hasActiveAssignment(DR_PEREZ, P1) && !$link->hasActiveAssignment(DRA_LOPEZ, P3));
$pdo->exec("UPDATE doctor_profiles SET status = 'suspended' WHERE local_user_ref_id = '" . DRA_LOPEZ . "'");
check('P-01b perfil médico suspendido → sin vínculo aunque la asignación siga activa', !$link->hasActiveAssignment(DRA_LOPEZ, P1));

// P-02 Episodios y expediente leídos de las columnas reales.
$repo = new PdoMedicalRecordRepository(hospital_pdo());
$episodes = $repo->episodesOf(P1);
check('P-02 episodios de P-0001 desde care_episodes (3, reciente primero) y expediente EXP-P-0001', count($episodes) === 3
    && $episodes[0]->type === 'inpatient' && $repo->findByPatient(P1)?->recordNumber === 'EXP-P-0001' && $repo->findByPatient(P2) === null);

// P-03 UNIQUE(local_patient_id): un segundo INSERT del mismo paciente devuelve el expediente existente.
$pdo = hospital_pdo();
$repo = new PdoMedicalRecordRepository($pdo);
$first = $repo->open(new MedicalRecord('10000000-0000-4000-8000-000000000001', P2, 'EXP-P-0002', date_utc('2026-10-23 12:00:00+00:00')), static fn () => null);
$second = $repo->open(new MedicalRecord('10000000-0000-4000-8000-000000000002', P2, 'EXP-P-0002', date_utc('2026-10-23 12:00:01+00:00')), static fn () => null);
$count = (int) $pdo->query("SELECT COUNT(*) FROM medical_records WHERE local_patient_id = '" . P2 . "'")->fetchColumn();
check('P-03 apertura concurrente: una sola fila y el segundo recibe el primero', $second->id === $first->id && $count === 1);

// P-04 Reversión: si la confirmación (auditoría) falla, el INSERT no queda.
$pdo = hospital_pdo();
$repo = new PdoMedicalRecordRepository($pdo);
try {
    $repo->open(new MedicalRecord('10000000-0000-4000-8000-000000000003', P2, 'EXP-P-0002', date_utc('2026-10-23 12:00:00+00:00')), static fn () => throw new \RuntimeException('auditoria'));
} catch (\RuntimeException) {
}
check('P-04 confirmación fallida → ROLLBACK, sin expediente para P-0002', $repo->findByPatient(P2) === null && !$pdo->inTransaction());

// P-05 Retención: la base impide borrar un expediente (trigger medical_records_retention).
try {
    $pdo->exec("DELETE FROM medical_records WHERE local_patient_id = '" . P1 . "'");
    $deleted = true;
} catch (\PDOException) {
    $deleted = false;
}
check('P-05 borrar un expediente es rechazado por el trigger de retención', $deleted === false);

// P-06 Error de persistencia traducido: tabla ausente → PersistenceFailure con código interno, sin SQL.
$broken = hospital_pdo();
$broken->exec('PRAGMA foreign_keys = OFF; DROP TABLE care_episodes');
try {
    (new PdoMedicalRecordRepository($broken))->episodesOf(P1);
    $message = 'sin error';
} catch (PersistenceFailure $e) {
    $message = $e->getMessage();
}
check('P-06 fallo del driver → PersistenceFailure("episodes_read_failed")', $message === 'episodes_read_failed');

// P-07 Bitácora REAL de Gobernanza (PdoAuditLog): la consulta queda en audit_log encadenada y sin datos clínicos.
$auditDb = audit_pdo();
$router = expediente_router(
    expediente_rbac(MEMBERSHIPS),
    new HospitalRegistry([HOSPITAL => static fn (): HospitalStores => expediente_pdo_stores(hospital_pdo())]),
    expediente_audit(pdo_audit_logger($auditDb), new SilentReporter()),
    new FixedClock(),
);
$response = call($router, 'GET', 'P-0001');
call($router, 'GET', 'P-0001', ENF_RUIZ);
$rows = $auditDb->query('SELECT event, result, hospital_id, detail, event_hash FROM audit_log ORDER BY occurred_at, event')->fetchAll(PDO::FETCH_ASSOC);
$events = array_map(static fn (array $r): string => $r['event'] . ':' . $r['result'], $rows);
$details = implode(' ', array_column($rows, 'detail'));
sort($events);
check('P-07 audit_log contiene consultado:success y acceso_denegado:denied del hospital-demo', $response['status'] === 200
    && $events === ['expediente_medico.acceso_denegado:denied', 'expediente_medico.consultado:success']
    && array_unique(array_column($rows, 'hospital_id')) === [HOSPITAL]);
check('P-07b los eventos llevan hash de cadena y no contienen nombres ni motivos clínicos', !in_array(null, array_column($rows, 'event_hash'), true)
    && !str_contains($details, 'Gómez') && !str_contains($details, 'Neumon'));

// P-08 Aislamiento por tenant con DOS bases: el mismo código de paciente en otro hospital es otra persona.
$norte = hospital_pdo(seed: false);
$norte->exec("INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('b0000000-0000-4000-8000-000000000001', 'f9', 'P-0001', 'Otra', 'Persona', '2000-01-01', 'male')");
$isolated = expediente_router(
    expediente_rbac(MEMBERSHIPS),
    new HospitalRegistry([HOSPITAL => static fn (): HospitalStores => expediente_pdo_stores(hospital_pdo()), OTRO_HOSPITAL => static fn (): HospitalStores => expediente_pdo_stores($norte)]),
    expediente_audit(new RecordingAuditLogger(), new SilentReporter()),
    new FixedClock(),
);
check('P-08 la misma médica: 200 en su hospital y 403 SIN_VINCULO en hospital-norte (otra base, otro paciente)',
    call($isolated, 'GET', 'P-0001')['status'] === 200 && code(call($isolated, 'GET', 'P-0001', DRA_LOPEZ, OTRO_HOSPITAL)) === '403 SIN_VINCULO_ASISTENCIAL');

// P-09 Conexión de un hospital que falla al abrirse → 503, sin afectar al otro.
$flaky = expediente_router(
    expediente_rbac(MEMBERSHIPS),
    new HospitalRegistry([HOSPITAL => static fn (): HospitalStores => expediente_pdo_stores(hospital_pdo()), OTRO_HOSPITAL => static fn (): HospitalStores => expediente_pdo_stores(new PDO('sqlite:/ruta/que/no/existe/h.sqlite'))]),
    expediente_audit(new RecordingAuditLogger(), new SilentReporter()),
    new FixedClock(),
);
check('P-09 base de hospital-norte inaccesible → 503 EXPEDIENTE_NO_DISPONIBLE; hospital-demo sigue en 200',
    code(call($flaky, 'GET', 'P-0001', DRA_LOPEZ, OTRO_HOSPITAL)) === '503 EXPEDIENTE_NO_DISPONIBLE' && call($flaky, 'GET', 'P-0001')['status'] === 200);
