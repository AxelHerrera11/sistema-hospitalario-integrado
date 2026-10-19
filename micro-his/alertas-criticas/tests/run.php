<?php

declare(strict_types=1);

use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Persistence\PdoAlertStore;
use MicroHis\Alertas\Presentation\CriticalAlertHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** Base de prueba: SQLite en memoria con el esquema del módulo y datos ficticios. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO lab_tests VALUES ('GLU-v1', 40, 400), ('HB-v1', NULL, NULL);
        INSERT INTO care_assignments VALUES ('EP-1', 'P-1', 'dra.lopez', 'active'), ('EP-1', 'P-1', 'dr.ruiz', 'ended')");
    $store = new PdoAlertStore($pdo);
    $handle = new HandleResultValidated($store, fn () => new DateTimeImmutable('2026-10-18 08:00', new DateTimeZone('America/Guatemala')), fn () => uniqid('ALR-'));
    return [$pdo, new CriticalAlertHttpHandler($handle, new AcknowledgeAlert($store))];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$evento = ['result_version_id' => 'RV-1', 'lab_test_id' => 'GLU-v1', 'patient_id' => 'P-1', 'episode_id' => 'EP-1', 'value' => 32];
$lectura = ['patient_id' => 'P-1', 'lab_test_id' => 'GLU-v1', 'value' => 32];
$evt = fn (array $change) => array_merge($evento, $change);
[$pdo, $http] = app();
[$status, $body] = $http->handle('POST', '/internal/result-validated', $evento, 'svc.validacion');
$id = $body['alert']['id'] ?? '';
check('glucosa 32 (crítico bajo 40) → 201 alerta low para la médica tratante, nivel 0', $status === 201 && $body['alert']['direction'] === 'low'
    && $body['alert']['recipient'] === 'dra.lopez' && $body['alert']['level'] === 0 && $body['alert']['dueAt'] === '2026-10-18T08:15:00-06:00');
check('la alerta queda auditada en la misma transacción', $pdo->query('SELECT event FROM local_access_audits')->fetchColumn() === 'alerta.generada');
check('evento repetido → 200 duplicate, sin segunda alerta', $http->handle('POST', '/internal/result-validated', $evento, 'svc.validacion')[1]['outcome'] === 'duplicate');
check('glucosa 95 o prueba sin umbrales → 200 not_critical', $http->handle('POST', '/internal/result-validated', $evt(['result_version_id' => 'RV-2', 'value' => 95]), 'svc.validacion')[1]['outcome'] === 'not_critical'
    && $http->handle('POST', '/internal/result-validated', $evt(['result_version_id' => 'RV-3', 'lab_test_id' => 'HB-v1']), 'svc.validacion')[1]['outcome'] === 'not_critical');
check('episodio sin médico → jefatura, nivel 1', $http->handle('POST', '/internal/result-validated', $evt(['result_version_id' => 'RV-4', 'episode_id' => 'EP-9', 'value' => 500]), 'svc.validacion')[1]['alert']['level'] === 1);
check('evento sin valor → 422 EVENT_INVALID', $http->handle('POST', '/internal/result-validated', $evt(['value' => null]), 'svc.validacion')[1] === ['error' => 'EVENT_INVALID', 'field' => 'value']);
$ack = fn (string $user, array $readBack, string $alert = '') => $http->handle('POST', '/critical-alerts/' . ($alert ?: $id) . '/acknowledge', ['read_back' => $readBack], $user);
check('lectura con otro valor → 422 READ_BACK_MISMATCH', $ack('dra.lopez', ['value' => 23] + $lectura)[0] === 422);
check('usuario sin relación de cuidado vigente → 403 NO_CARE_RELATION', $ack('dr.ruiz', $lectura)[0] === 403);
check('alerta inexistente → 404 ALERT_NOT_FOUND', $ack('dra.lopez', $lectura, 'ALR-X')[0] === 404);
[$status, $body] = $ack('dra.lopez', $lectura);
check('acuse correcto → 200 acknowledged', $status === 200 && $body['alert']['status'] === 'acknowledged' && $body['alert']['acknowledgedBy'] === 'dra.lopez');
check('segundo acuse → 409 ALERT_ALREADY_ACKNOWLEDGED', $ack('dra.lopez', $lectura)[0] === 409);
check('sin usuario → 401', $http->handle('POST', '/internal/result-validated', $evento, null)[0] === 401);
[$pdo, $http] = app();
$pdo->exec('DROP TABLE local_access_audits');
check('falla de auditoría → 500 y rollback sin alerta', $http->handle('POST', '/internal/result-validated', $evento, 'svc.validacion')[0] === 500
    && (int) $pdo->query('SELECT COUNT(*) FROM critical_alerts')->fetchColumn() === 0);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
