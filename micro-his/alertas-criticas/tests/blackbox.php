<?php

declare(strict_types=1);

use MicroHis\Alertas\Application\AcknowledgeAlert;
use MicroHis\Alertas\Application\GetInbox;
use MicroHis\Alertas\Application\HandleResultValidated;
use MicroHis\Alertas\Persistence\InMemoryAlertRepository;
use MicroHis\Alertas\Presentation\AlertInboxView;
use MicroHis\Alertas\Presentation\ApiKernel;
use MicroHis\Alertas\Presentation\CriticalAlertController;

require __DIR__ . '/../bootstrap.php';

/** Caja negra: solo el contrato HTTP de openapi.yaml, con particiones de equivalencia y valores límite. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
function server(): ApiKernel
{
    $repo = new InMemoryAlertRepository(['GLU-v1' => ['critical_low' => 40.0, 'critical_high' => 400.0]], [['EP-1', 'P-1', 'dra.lopez']]);
    $c = new CriticalAlertController(new HandleResultValidated($repo, fn () => new DateTimeImmutable(), fn () => uniqid('A-')), new AcknowledgeAlert($repo), new GetInbox($repo), new AlertInboxView());
    return new ApiKernel($c, 'HOSP-DEMO', fn () => true);
}
$h = ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'svc.validacion'];
$event = fn (array $change) => json_encode(array_merge(['result_version_id' => uniqid('RV-'), 'lab_test_id' => 'GLU-v1', 'patient_id' => 'P-1', 'episode_id' => 'EP-1', 'value' => 32], $change));
$publish = function (ApiKernel $k, array $change) use ($h, $event): array {
    [$status, , $out] = $k->handle('POST', '/api/v1/internal/result-validated', $h, $event($change));
    $body = json_decode($out, true);
    return [$status, $body['outcome'] ?? $body['code']];
};
// Valores límite alrededor del crítico bajo (40) y del crítico alto (400).
foreach ([[39.9, 201, 'created'], [40, 200, 'not_critical'], [40.1, 200, 'not_critical'], [399.9, 200, 'not_critical'], [400, 200, 'not_critical'], [400.1, 201, 'created']] as [$value, $status, $outcome]) {
    check("value {$value} → {$status} {$outcome}", $publish(server(), ['value' => $value]) === [$status, $outcome]);
}
// Partición: prueba con y sin umbrales.
check('lab_test_id sin umbrales en el catálogo → 200 not_critical', $publish(server(), ['lab_test_id' => 'HB-v1', 'value' => 1]) === [200, 'not_critical']);
// Partición: campos obligatorios del evento (ausente o de otro tipo).
check('value ausente → 422 EVENT_INVALID', $publish(server(), ['value' => null]) === [422, 'EVENT_INVALID']);
check('patient_id numérico → 422 EVENT_INVALID', $publish(server(), ['patient_id' => 7]) === [422, 'EVENT_INVALID']);
check('value 1e400 (infinito) → 422 EVENT_INVALID', $publish(server(), ['value' => '1e400']) === [422, 'EVENT_INVALID']);
// Secuencia: el mismo resultado dos veces (idempotencia del contrato).
$k = server();
$same = ['result_version_id' => 'RV-X'];
check('primer evento → 201 created', $publish($k, $same) === [201, 'created']);
check('mismo result_version_id otra vez → 200 duplicate', $publish($k, $same) === [200, 'duplicate']);
echo 'Todas las pruebas de caja negra pasaron.' . PHP_EOL;
