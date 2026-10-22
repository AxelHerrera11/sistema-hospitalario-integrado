<?php

declare(strict_types=1);

use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\GetInbox;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Persistence\InMemoryAlertRepository;
use MicroHis\Alertas\Persistence\PdoAlertRepository;
use MicroHis\Alertas\Presentation\AlertInboxView;
use MicroHis\Alertas\Presentation\CriticalAlertController;

require __DIR__ . '/../bootstrap.php';

/**
 * Prueba de contrato: la misma batería corre con los dos adaptadores del Repository.
 * $probe da lo que cada uno expone distinto: alertas guardadas, primer evento auditado y cómo simular la falla.
 */
function app(string $adapter): array
{
    if ($adapter === 'InMemory') {
        $repo = new InMemoryAlertRepository(['GLU-v1' => ['critical_low' => 40.0, 'critical_high' => 400.0]], [['EP-1', 'P-1', 'dra.lopez']]);
        $probe = ['count' => fn () => count($repo->rows), 'audit' => fn () => $repo->audit[0][0] ?? null, 'fail' => fn () => $repo->failWrites = true];
    } else {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
        $pdo->exec("INSERT INTO lab_tests VALUES ('GLU-v1', 40, 400), ('HB-v1', NULL, NULL);
            INSERT INTO care_assignments VALUES ('EP-1', 'P-1', 'dra.lopez', 'active'), ('EP-1', 'P-1', 'dr.ruiz', 'ended')");
        $repo = new PdoAlertRepository($pdo);
        $probe = ['count' => fn () => (int) $pdo->query('SELECT COUNT(*) FROM critical_alerts')->fetchColumn(),
            'audit' => fn () => $pdo->query('SELECT action FROM local_access_audits')->fetchColumn(), 'fail' => fn () => $pdo->exec('DROP TABLE local_access_audits')];
    }
    $handle = new HandleResultValidated($repo, fn () => new DateTimeImmutable('2026-10-18 08:00', new DateTimeZone('America/Guatemala')), fn () => uniqid('ALR-'));
    return [new CriticalAlertController($handle, new AcknowledgeAlert($repo), new GetInbox($repo), new AlertInboxView()), $probe];
}

function check(string $name, bool $ok): void
{
    global $adapter;
    echo ($ok ? 'OK' : 'FALLO') . ": [{$adapter}] {$name}" . PHP_EOL;
    $ok || exit(1);
}
$evento = ['result_version_id' => 'RV-1', 'lab_test_id' => 'GLU-v1', 'patient_id' => 'P-1', 'episode_id' => 'EP-1', 'value' => 32];
$lectura = ['patient_id' => 'P-1', 'lab_test_id' => 'GLU-v1', 'value' => 32];
$evt = fn (array $change) => array_merge($evento, $change);
foreach (['PDO/SQLite', 'InMemory'] as $adapter) {
    [$http, $probe] = app($adapter);
    $post = fn (array $event) => $http->handle('POST', '/internal/result-validated', $event, 'svc.validacion');
    [$status, $body] = $post($evento);
    $id = $body['alert']['id'] ?? '';
    check('glucosa 32 (crítico bajo 40) → 201 alerta low para la médica tratante, nivel 0', $status === 201 && $body['alert']['direction'] === 'low'
        && $body['alert']['recipient'] === 'dra.lopez' && $body['alert']['level'] === 0 && $body['alert']['dueAt'] === '2026-10-18T08:15:00-06:00');
    check('la alerta queda auditada', $probe['audit']() === 'alerta.generada');
    check('evento repetido → 200 duplicate, sin segunda alerta', $post($evento)[1]['outcome'] === 'duplicate' && $probe['count']() === 1);
    check('glucosa 95 o prueba sin umbrales → 200 not_critical', $post($evt(['result_version_id' => 'RV-2', 'value' => 95]))[1]['outcome'] === 'not_critical'
        && $post($evt(['result_version_id' => 'RV-3', 'lab_test_id' => 'HB-v1']))[1]['outcome'] === 'not_critical');
    check('episodio sin médico → jefatura, nivel 1', $post($evt(['result_version_id' => 'RV-4', 'episode_id' => 'EP-9', 'value' => 500]))[1]['alert']['level'] === 1);
    check('evento sin valor → 422 EVENT_INVALID', $post($evt(['value' => null]))[1] === ['error' => 'EVENT_INVALID', 'field' => 'value']);
    $inbox = $http->handle('GET', '/critical-alerts', [], 'dra.lopez')[1]['alerts'];
    check('bandeja de la médica → solo su alerta pendiente', array_column($inbox, 'id') === [$id]);
    $html = $http->handle('GET', '/critical-alerts', ['format' => 'html'], 'dra.lopez')[1];
    check('View HTML: alerta con role="alert", valor y dirección', str_contains($html, 'role="alert"') && str_contains($html, 'GLU-v1 32 (bajo el umbral crítico)'));
    $ack = fn (string $user, array $readBack, string $alert = '') => $http->handle('POST', '/critical-alerts/' . ($alert ?: $id) . '/acknowledge', ['read_back' => $readBack], $user);
    check('lectura con otro valor → 422 READ_BACK_MISMATCH', $ack('dra.lopez', ['value' => 23] + $lectura)[0] === 422);
    check('usuario sin relación de cuidado vigente → 403 NO_CARE_RELATION', $ack('dr.ruiz', $lectura)[0] === 403);
    check('alerta inexistente → 404 ALERT_NOT_FOUND', $ack('dra.lopez', $lectura, 'ALR-X')[0] === 404);
    [$status, $body] = $ack('dra.lopez', $lectura);
    check('acuse correcto → 200 acknowledged y sale de la bandeja', $status === 200 && $body['alert']['status'] === 'acknowledged'
        && $http->handle('GET', '/critical-alerts', [], 'dra.lopez')[1]['alerts'] === []);
    check('segundo acuse → 409 ALERT_ALREADY_ACKNOWLEDGED', $ack('dra.lopez', $lectura)[0] === 409);
    check('sin usuario → 401', $http->handle('GET', '/critical-alerts', [], null)[0] === 401);
    [$http, $probe] = app($adapter);
    $probe['fail']();
    check('falla de persistencia → 500 y no queda alerta', $http->handle('POST', '/internal/result-validated', $evento, 'svc.validacion')[0] === 500 && $probe['count']() === 0);
}
echo 'Todas las pruebas pasaron.' . PHP_EOL;
