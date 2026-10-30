<?php

declare(strict_types=1);

use MicroHis\Alertas\Domain\CriticalAlert;
use MicroHis\Alertas\Domain\DomainRuleViolation;

require __DIR__ . '/../bootstrap.php';

/**
 * Caja blanca: un caso por rama de CriticalAlert::detect (B1–B5) y de acknowledge (B6–B11).
 * La tabla de ramas está en docs/alertas-criticas/semana-15/README.md.
 */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
$now = new DateTimeImmutable('2026-10-18 08:00', new DateTimeZone('America/Guatemala'));
$event = ['result_version_id' => 'RV-1', 'lab_test_id' => 'GLU-v1', 'patient_id' => 'P-1', 'episode_id' => 'EP-1', 'value' => 32];
$t = ['critical_low' => 40.0, 'critical_high' => 400.0];
$detect = fn (array $change, ?array $th = null, ?string $att = 'dra.lopez') => CriticalAlert::detect('A-1', array_merge($event, $change), func_num_args() >= 2 ? $th : $t, $att, $now);
check('B1 la prueba no tiene umbrales → sin alerta', $detect([], null) === null);
check('B2 valor < crítico bajo → low', $detect(['value' => 39.9])?->direction === 'low');
check('B2 valor = crítico bajo (límite de <) → sin alerta', $detect(['value' => 40]) === null);
check('B3 valor > crítico alto → high', $detect(['value' => 400.1])?->direction === 'high');
check('B3 valor = crítico alto (límite de >) → sin alerta', $detect(['value' => 400]) === null);
$a = $detect([]);
check('B4 con médico tratante → nivel 0, destinatario el médico y plazo de 15 min', $a->level === 0 && $a->recipient === 'dra.lopez' && $a->dueAt === '2026-10-18T08:15:00-06:00');
$b = $detect([], $t, null);
check('B5 sin médico tratante → nivel 1, jefatura de servicio', $b->level === 1 && $b->recipient === CriticalAlert::SERVICE_HEAD);
$readBack = ['patient_id' => 'P-1', 'lab_test_id' => 'GLU-v1', 'value' => 32];
$ack = function (string $user, array $rb, bool $cares, ?CriticalAlert $alert = null) use ($detect, $readBack): ?string {
    $alert ??= $detect([]);
    try { $alert->acknowledge($user, array_merge($readBack, $rb), $cares); return $alert->status; } catch (DomainRuleViolation $v) { return $v->rule; }
};
check('B6 destinatario sin relación de cuidado registrada → se acepta (es el destinatario)', $ack('dra.lopez', [], false) === 'acknowledged');
check('B7 otro usuario con relación de cuidado → se acepta', $ack('dr.ruiz', [], true) === 'acknowledged');
check('B8 otro usuario sin relación de cuidado → NO_CARE_RELATION', $ack('dr.ruiz', [], false) === 'NO_CARE_RELATION');
check('B9 lectura con otro paciente → READ_BACK_MISMATCH', $ack('dra.lopez', ['patient_id' => 'P-2'], false) === 'READ_BACK_MISMATCH');
check('B9 lectura con otra prueba → READ_BACK_MISMATCH', $ack('dra.lopez', ['lab_test_id' => 'K-v1'], false) === 'READ_BACK_MISMATCH');
check('B10 lectura con valor no numérico → READ_BACK_MISMATCH', $ack('dra.lopez', ['value' => 'treinta y dos'], false) === 'READ_BACK_MISMATCH');
check('B10 lectura «32.0» como texto numérico → se acepta', $ack('dra.lopez', ['value' => '32.0'], false) === 'acknowledged');
$done = $detect([]);
$done->acknowledge('dra.lopez', $readBack, false);
check('B11 alerta ya acusada → ALERT_ALREADY_ACKNOWLEDGED (antes que la lectura)', $ack('dra.lopez', ['value' => 1], false, $done) === 'ALERT_ALREADY_ACKNOWLEDGED');
echo 'Todas las pruebas de caja blanca pasaron.' . PHP_EOL;
