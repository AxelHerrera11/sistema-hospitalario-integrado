<?php

declare(strict_types=1);

use MicroHis\OrdenesLab\Client\LabOrderClient;

require __DIR__ . '/../bootstrap.php';

$failures = 0;
function check(string $label, bool $condition): void
{
    global $failures;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $condition ? 0 : 1;
}

$baseUrl = (string) (getenv('MICROHIS_BASE_URL') ?: 'http://127.0.0.1:8091');
$tenant = (string) (getenv('MICROHIS_TENANT') ?: 'hosp-a');
$user = (string) (getenv('MICROHIS_USER') ?: 'medico-1');
$client = new LabOrderClient($baseUrl, $tenant, $user);

$created = $client->create([
    'local_patient_id' => 'pat-1', 'care_episode_id' => 'epi-1', 'clinical_note_id' => 'note-1',
    'priority' => 'ROUTINE', 'clinical_context' => 'Prueba del cliente HTTP',
    'idempotency_key' => 'client-' . bin2hex(random_bytes(8)),
    'items' => [['lab_test_id' => 'test-glu']],
]);
check('cliente crea la orden por HTTP → 201', $created['status'] === 201);
$orderId = (string) ($created['body']['lab_order']['lab_order_id'] ?? '');

$fetched = $client->get($orderId);
check('cliente consulta la orden → 200', $fetched['status'] === 200 && $fetched['body']['lab_order']['status'] === 'ISSUED');

$advanced = $client->advance($orderId, 'COLLECTED');
check('cliente avanza a COLLECTED → 200', $advanced['status'] === 200 && $advanced['body']['lab_order']['status'] === 'COLLECTED');

$cancelled = $client->cancel($orderId, 'Prueba del cliente HTTP');
check('cliente cancela con motivo → 200', $cancelled['status'] === 200 && $cancelled['body']['lab_order']['status'] === 'CANCELLED');

$listed = $client->list(['status' => 'CANCELLED']);
check('cliente lista por estado → 200', $listed['status'] === 200 && count($listed['body']['lab_orders']) >= 1);

try {
    (new LabOrderClient('http://127.0.0.1:1', $tenant, $user, 0.3))->get('no-existe');
    check('servidor caído lanza LabOrdersUnavailable', false);
} catch (\MicroHis\OrdenesLab\Client\LabOrdersUnavailable) {
    check('servidor caído lanza LabOrdersUnavailable', true);
}

echo $failures === 0 ? "OK: verificaciones del cliente aprobadas\n" : "FALLARON {$failures} verificaciones\n";
exit($failures === 0 ? 0 : 1);
