<?php

declare(strict_types=1);

use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Persistence\PdoAdmissionStore;
use MicroHis\Admision\Presentation\AdmissionHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** Base de prueba: SQLite en memoria con el esquema del módulo y datos ficticios. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO local_patients VALUES ('P-1'), ('P-2'), ('P-3');
        INSERT INTO beds VALUES ('B-101', 'MED-101', 'adult', 'available'), ('B-102', 'MED-102', 'adult', 'available'),
            ('B-201', 'PED-201', 'pediatric', 'available'), ('B-301', 'MED-301', 'adult', 'maintenance')");
    $store = new PdoAdmissionStore($pdo);
    $admit = new AdmitPatient($store, fn () => '2026-10-18T09:00:00-06:00', fn () => uniqid('ID-'));
    return [$pdo, new AdmissionHttpHandler($admit, new FindAvailableBeds($store))];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$admision = ['patient_id' => 'P-1', 'bed_id' => 'B-101', 'bed_type' => 'adult', 'reason' => 'Neumonía, requiere oxígeno'];
$count = fn (PDO $pdo, string $table): int => (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
[$pdo, $http] = app();
[$status, $body] = $http->handle('POST', '/admissions', $admision, 'admision1');
check('admisión válida → 201 con episodio y estancia', $status === 201 && $body['admission']['bedId'] === 'B-101');
check('la cama queda ocupada y el episodio inpatient abierto', $pdo->query("SELECT status FROM beds WHERE bed_id = 'B-101'")->fetchColumn() === 'occupied'
    && $pdo->query("SELECT type || '/' || status FROM care_episodes")->fetchColumn() === 'inpatient/open');
check('la asignación queda auditada en la misma transacción', $pdo->query('SELECT action FROM local_access_audits')->fetchColumn() === 'admision.cama_asignada');
check('otra admisión a la misma cama → 409 BED_NOT_AVAILABLE', $http->handle('POST', '/admissions', ['patient_id' => 'P-2'] + $admision, 'admision1')[1]['error'] === 'BED_NOT_AVAILABLE');
check('el mismo paciente en otra cama → 409 PATIENT_ALREADY_ADMITTED', $http->handle('POST', '/admissions', ['bed_id' => 'B-102'] + $admision, 'admision1')[1]['error'] === 'PATIENT_ALREADY_ADMITTED');
foreach ([
    'cama en mantenimiento → 409 BED_NOT_AVAILABLE' => [['bed_id' => 'B-301'], 409, 'BED_NOT_AVAILABLE'],
    'cama pediátrica para un adulto → 422 BED_TYPE_MISMATCH' => [['bed_id' => 'B-201'], 422, 'BED_TYPE_MISMATCH'],
    'sin motivo → 422 REASON_REQUIRED' => [['bed_id' => 'B-102', 'reason' => '  '], 422, 'REASON_REQUIRED'],
    'paciente inexistente → 404 PATIENT_NOT_FOUND' => [['patient_id' => 'P-9'], 404, 'PATIENT_NOT_FOUND'],
    'cama inexistente → 404 BED_NOT_FOUND' => [['bed_id' => 'B-999'], 404, 'BED_NOT_FOUND'],
] as $name => [$change, $code, $rule]) {
    [$status, $body] = $http->handle('POST', '/admissions', array_merge($admision, ['patient_id' => 'P-2'], $change), 'admision1');
    check($name, $status === $code && $body['error'] === $rule);
}
check('sin usuario → 401', $http->handle('POST', '/admissions', $admision, null)[0] === 401);
check('ninguna admisión rechazada dejó episodios ni estancias', $count($pdo, 'care_episodes') === 1 && $count($pdo, 'bed_stays') === 1);
[$status, $body] = $http->handle('GET', '/beds/available', ['type' => 'adult'], 'admision1');
check('camas adultas disponibles → solo MED-102', $status === 200 && array_column($body['beds'], 'code') === ['MED-102']);
[$pdo, $http] = app();
$pdo->exec('DROP TABLE local_access_audits');
check('falla de auditoría → 500 PERSISTENCE_FAILURE', $http->handle('POST', '/admissions', $admision, 'admision1')[0] === 500);
check('rollback: la cama sigue disponible y no quedó episodio', $pdo->query("SELECT status FROM beds WHERE bed_id = 'B-101'")->fetchColumn() === 'available'
    && $count($pdo, 'care_episodes') === 0);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
