<?php

declare(strict_types=1);

use MicroHis\OrdenesLab\Application\AdvanceLabOrder;
use MicroHis\OrdenesLab\Application\CancelLabOrder;
use MicroHis\OrdenesLab\Application\FindLabOrder;
use MicroHis\OrdenesLab\Application\IssueLabOrder;
use MicroHis\OrdenesLab\Application\ListLabOrders;
use MicroHis\OrdenesLab\Persistence\InMemoryLabOrderRepository;
use MicroHis\OrdenesLab\Presentation\ApiKernel;
use MicroHis\OrdenesLab\Presentation\LabOrderController;
use MicroHis\OrdenesLab\Presentation\LabOrderPresenter;
use MicroHis\OrdenesLab\Presentation\LabOrderView;
use MicroHis\OrdenesLab\Tests\Fixtures\InMemoryExpedienteGateway;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/InMemoryExpedienteGateway.php';

$failures = 0;
function check(string $label, bool $condition): void
{
    global $failures;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$label}\n";
    $failures += $condition ? 0 : 1;
}

$gateway = new InMemoryExpedienteGateway(
    ['hosp-a|pat-1|epi-1' => ['patient_id' => 'pat-1', 'episode_id' => 'epi-1', 'open' => true, 'clinical_note_ids' => ['note-1']]],
    ['hosp-a' => [
        'test-glu' => ['code' => 'GLU', 'name' => 'Glucosa'],
        'test-hb' => ['code' => 'HB', 'name' => 'Hemograma'],
    ]],
);
$repo = new InMemoryLabOrderRepository();
$sequence = 0;
$next = static function () use (&$sequence): string {
    return 'seg-' . (++$sequence);
};
$now = static fn (): string => '2026-10-22T11:00:00-06:00';
$kernel = new ApiKernel(
    new LabOrderController(
        new IssueLabOrder($repo, $gateway, $next, $next, $now, $next),
        new AdvanceLabOrder($repo, $next, $now),
        new CancelLabOrder($repo, $next, $now),
        new FindLabOrder($repo),
        new ListLabOrders($repo),
        new LabOrderPresenter(),
        new LabOrderView(),
    ),
    static fn (): string => 'req-generado',
);

$permissions = 'laboratorio.ordenes.crear,laboratorio.ordenes.ver,laboratorio.ordenes.actualizar,laboratorio.ordenes.cancelar';
$auth = ['X-Tenant-ID' => 'hosp-a', 'X-User-Ref' => 'medico-1', 'X-Permissions' => $permissions];
$draft = [
    'local_patient_id' => 'pat-1', 'care_episode_id' => 'epi-1', 'clinical_note_id' => 'note-1',
    'priority' => 'URGENT', 'clinical_context' => 'Control', 'idempotency_key' => 'api-key-1',
    'items' => [['lab_test_id' => 'test-glu'], ['lab_test_id' => 'test-hb']],
];

$health = $kernel->handle('GET', '/health', ['X-Request-ID' => 'req-health'], []);
check('/health sin credenciales → 200', $health['status'] === 200 && $health['headers']['X-Request-ID'] === 'req-health');

$noTenant = $kernel->handle('GET', '/api/v1/lab-orders', [], []);
check('sin X-Tenant-ID → 400', $noTenant['status'] === 400 && $noTenant['body']['code'] === 'TENANT_REQUIRED');
check('el error usa problem+json', str_contains($noTenant['headers']['Content-Type'], 'application/problem+json'));
check('el error incluye request_id generado', $noTenant['body']['request_id'] === 'req-generado');

$noUser = $kernel->handle('GET', '/api/v1/lab-orders', ['X-Tenant-ID' => 'hosp-a'], []);
check('sin X-User-Ref → 401', $noUser['status'] === 401 && $noUser['body']['code'] === 'UNAUTHENTICATED');

$forbidden = $kernel->handle('POST', '/api/v1/lab-orders', ['X-Tenant-ID' => 'hosp-a', 'X-User-Ref' => 'auditor-1', 'X-Permissions' => 'laboratorio.ordenes.ver'], $draft);
check('sin permiso de creación → 403', $forbidden['status'] === 403 && $forbidden['body']['code'] === 'FORBIDDEN');

$created = $kernel->handle('POST', '/api/v1/lab-orders', $auth + ['X-Request-ID' => 'req-create'], $draft);
check('emisión → 201 con X-Request-ID', $created['status'] === 201 && $created['headers']['X-Request-ID'] === 'req-create');
$orderId = (string) $created['body']['lab_order']['lab_order_id'];

$replay = $kernel->handle('POST', '/api/v1/lab-orders', $auth, $draft);
check('misma Idempotency-Key recupera con 200', $replay['status'] === 200 && $replay['body']['lab_order']['lab_order_id'] === $orderId);

$fromHeader = $kernel->handle('POST', '/api/v1/lab-orders', $auth + ['Idempotency-Key' => 'api-key-2'], array_diff_key($draft, ['idempotency_key' => true]));
check('Idempotency-Key por cabecera es aceptada', $fromHeader['status'] === 201);

check('GET orden → 200', $kernel->handle('GET', "/api/v1/lab-orders/{$orderId}", $auth, [])['status'] === 200);
check('otro tenant no ve la orden → 404', $kernel->handle('GET', "/api/v1/lab-orders/{$orderId}", ['X-Tenant-ID' => 'hosp-b', 'X-User-Ref' => 'medico-1', 'X-Permissions' => 'laboratorio.ordenes.ver'], [])['status'] === 404);

$advanced = $kernel->handle('PATCH', "/api/v1/lab-orders/{$orderId}/status", $auth, ['status' => 'COLLECTED']);
check('PATCH de estado → 200', $advanced['status'] === 200 && $advanced['body']['lab_order']['status'] === 'COLLECTED');
$invalid = $kernel->handle('PATCH', "/api/v1/lab-orders/{$orderId}/status", $auth, ['status' => 'VALIDATED']);
check('transición inválida → 409', $invalid['status'] === 409 && $invalid['body']['code'] === 'INVALID_STATUS_TRANSITION');
check('el 409 conserva el campo', ($invalid['body']['field'] ?? '') === 'status');

$cancel = $kernel->handle('POST', "/api/v1/lab-orders/{$orderId}/cancel", $auth, ['reason' => 'Estudio duplicado']);
check('cancelación → 200 CANCELLED', $cancel['status'] === 200 && $cancel['body']['lab_order']['status'] === 'CANCELLED');

$missingKey = $kernel->handle('POST', '/api/v1/lab-orders', $auth, array_diff_key($draft, ['idempotency_key' => true]));
check('sin Idempotency-Key → 422', $missingKey['status'] === 422 && $missingKey['body']['code'] === 'IDEMPOTENCY_KEY_REQUIRED');

check('ruta desconocida → 404', $kernel->handle('GET', '/api/v9/lab-orders', $auth, [])['status'] === 404);
check('método desconocido → 404', $kernel->handle('DELETE', '/api/v1/lab-orders', $auth, [])['status'] === 404);

echo $failures === 0 ? "OK: verificaciones de API aprobadas\n" : "FALLARON {$failures} verificaciones\n";
exit($failures === 0 ? 0 : 1);
