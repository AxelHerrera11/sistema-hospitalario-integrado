<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\RecordVitalSigns;
use MicroHis\SignosVitales\Domain\GlucoseRule;
use MicroHis\SignosVitales\Domain\HeartRateRule;
use MicroHis\SignosVitales\Domain\NoAlertRule;
use MicroHis\SignosVitales\Domain\OxygenSaturationRule;
use MicroHis\SignosVitales\Domain\RespiratoryRateRule;
use MicroHis\SignosVitales\Domain\Severity;
use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
use MicroHis\SignosVitales\Domain\VitalSignRule;
use MicroHis\SignosVitales\Domain\VitalSigns;
use MicroHis\SignosVitales\Persistence\PdoVitalSignStore;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
use MicroHis\SignosVitales\Presentation\VitalSignHttpHandler;

require __DIR__ . '/../bootstrap.php';

/** @return list<VitalSignRule> reglas del perfil ilustrativo adulto-2026.1 */
function rules(): array
{
    return [new HeartRateRule(), new OxygenSaturationRule(), new GlucoseRule(), new RespiratoryRateRule(), new NoAlertRule('weight'), new NoAlertRule('height')];
}

/** Base de prueba: SQLite en memoria con datos ficticios. ep1 es de un adulto, ep2 de un niño y ep3 está cerrado. */
function app(): array
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $pdo->exec("INSERT INTO care_episodes VALUES ('ep1', 'open', '1980-05-10'), ('ep2', 'open', '2017-03-01'), ('ep3', 'closed', '1975-01-20')");
    $pdo->exec("INSERT INTO care_assignments VALUES ('ep1', 'dra.lopez', 'active'), ('ep2', 'dra.lopez', 'active'), ('ep3', 'dra.lopez', 'active'), ('ep1', 'dr.perez', 'ended')");
    $store = new PdoVitalSignStore($pdo);
    $notifier = new QueuedAlertNotifier();
    return [$pdo, new VitalSignHttpHandler(new RecordVitalSigns($store, new ThresholdEvaluator(rules()), $store, $notifier)), $notifier];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

/** Prueba de contrato LSP: la misma para cada subtipo, a través del tipo base. */
function honorsContract(VitalSignRule $rule): bool
{
    try {
        $sinElSigno = $rule->evaluate(new VitalSigns(['temperature' => 36.8]));
        $completa = $rule->evaluate(new VitalSigns(['heart_rate' => 80, 'oxygen_saturation' => 98, 'glucose' => 100, 'respiratory_rate' => 16, 'weight' => 70, 'height' => 170]));
    } catch (Throwable) {
        return false;
    }
    return $sinElSigno === Severity::None && $completa === Severity::None;
}

$col = static fn (PDO $pdo, string $sql): array => $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
$status = static fn (array $r): string => $r['status'] . ' ' . ($r['body']['code'] ?? '');
$lopez = ['X-User-ID' => 'dra.lopez'];
$hora = ['measured_at' => '2026-10-14 08:30:00'];

[$pdo, $http, $notifier] = app();
$r = $http->record($lopez, 'ep1', ['heart_rate' => 138, 'oxygen_saturation' => 94] + $hora);
check('camino feliz: FC 138 + SpO2 94 -> 201 alerta critical con dos hallazgos', $r['status'] === 201 && $r['body']['severity'] === 'critical' && $r['body']['findings'] === ['heart_rate' => 'critical', 'oxygen_saturation' => 'warning']);
check('camino feliz: alerta auditada en la transacción y notificada después', in_array('signo_vital.alerta_generada', $col($pdo, 'SELECT event FROM local_access_audits'), true) && count($notifier->queue) === 1);
check('camino feliz: FC 80 y peso 70 -> 201 sin alerta ni notificación', $status($http->record($lopez, 'ep1', ['heart_rate' => 80, 'weight' => 70] + $hora)) === '201 OK' && count($notifier->queue) === 1);
check('RF-SV-03: solo FC 95 -> warning; la glucosa ausente no bloquea', $http->record($lopez, 'ep1', ['heart_rate' => 95] + $hora)['body']['severity'] === 'warning');

check('regla de dominio: sin signos -> 422', $status($http->record($lopez, 'ep1', $hora)) === '422 VITAL_SIGNS_INVALID');
check('regla de dominio: temperatura 50 °C -> 422 antes de evaluar', $status($http->record($lopez, 'ep1', ['temperature' => 50] + $hora)) === '422 VITAL_SIGN_IMPLAUSIBLE');
check('regla de dominio: asignación terminada -> 403', $status($http->record(['X-User-ID' => 'dr.perez'], 'ep1', ['heart_rate' => 80] + $hora)) === '403 NOT_TREATING_PHYSICIAN');
check('regla de dominio: episodio cerrado -> 409', $status($http->record($lopez, 'ep3', ['heart_rate' => 80] + $hora)) === '409 EPISODE_CLOSED');
$enCola = count($notifier->queue);
check('RF-SV-06: paciente de 9 años -> 201 THRESHOLDS_NOT_APPLICABLE, auditado y sin alerta', $status($http->record($lopez, 'ep2', ['heart_rate' => 138] + $hora)) === '201 THRESHOLDS_NOT_APPLICABLE'
    && in_array('signo_vital.sin_evaluacion', $col($pdo, 'SELECT event FROM local_access_audits'), true) && count($notifier->queue) === $enCola);
check('validación: measured_at inválido -> 422', $status($http->record($lopez, 'ep1', ['heart_rate' => 80, 'measured_at' => '14/10/2026'])) === '422 MEASURED_AT_INVALID');
$filas = $col($pdo, 'SELECT COUNT(*) FROM vital_signs');
check('validación de tipos: encabezado o measured_at no string -> 422 sin guardar', $status($http->record(['X-User-ID' => 7], 'ep1', ['heart_rate' => 80] + $hora)) === '422 PAYLOAD_INVALID'
    && $status($http->record($lopez, 'ep1', ['heart_rate' => 80, 'measured_at' => ['2026-10-14 08:30:00']])) === '422 PAYLOAD_INVALID' && $col($pdo, 'SELECT COUNT(*) FROM vital_signs') === $filas);
check('LSP: las 6 reglas cumplen la misma prueba de contrato', count(array_filter(rules(), 'honorsContract')) === 6);

[$pdo, $http, $notifier] = app();
$pdo->exec('DROP TABLE local_access_audits');
check('error de persistencia: auditoría caída -> 500, rollback y sin notificación', $status($http->record($lopez, 'ep1', ['heart_rate' => 138] + $hora)) === '500 VITAL_SIGN_PERSISTENCE_ERROR'
    && $col($pdo, 'SELECT COUNT(*) FROM vital_signs') === [0] && $notifier->queue === []);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
