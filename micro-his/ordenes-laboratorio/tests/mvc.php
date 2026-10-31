<?php

declare(strict_types=1);

use MicroHis\OrdenesLab\Application\AdvanceLabOrder;
use MicroHis\OrdenesLab\Application\CancelLabOrder;
use MicroHis\OrdenesLab\Application\FindLabOrder;
use MicroHis\OrdenesLab\Application\IssueLabOrder;
use MicroHis\OrdenesLab\Application\ListLabOrders;
use MicroHis\OrdenesLab\Persistence\InMemoryLabOrderRepository;
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
        'test-na' => ['code' => 'NA', 'name' => 'Sodio <b>'],
    ]],
);
$repo = new InMemoryLabOrderRepository();
$sequence = 0;
$next = static function () use (&$sequence): string {
    return 'id-' . (++$sequence);
};
$now = static fn (): string => '2026-10-22T10:00:00-06:00';
$presenter = new LabOrderPresenter();
$controller = new LabOrderController(
    new IssueLabOrder($repo, $gateway, $next, $next, $now, $next),
    new AdvanceLabOrder($repo, $next, $now),
    new CancelLabOrder($repo, $next, $now),
    new FindLabOrder($repo),
    new ListLabOrders($repo),
    $presenter,
    new LabOrderView(),
);

$draft = [
    'local_patient_id' => 'pat-1', 'care_episode_id' => 'epi-1', 'clinical_note_id' => 'note-1',
    'priority' => 'URGENT', 'clinical_context' => 'Control', 'idempotency_key' => 'mvc-key-1',
    'items' => [['lab_test_id' => 'test-glu'], ['lab_test_id' => 'test-hb'], ['lab_test_id' => 'test-na']],
];

check('sin usuario → 401', $controller->handle('POST', '/lab-orders', $draft, null, 'hosp-a')['status'] === 401);
$created = $controller->handle('POST', '/lab-orders', $draft, 'medico-1', 'hosp-a');
check('el controlador delega la emisión → 201', $created['status'] === 201);
$orderId = (string) $created['body']['lab_order']['lab_order_id'];

$shown = $controller->handle('GET', "/lab-orders/{$orderId}", [], 'medico-1', 'hosp-a');
check('consulta JSON de la orden → 200', $shown['status'] === 200 && $shown['body']['lab_order']['status'] === 'ISSUED');
$html = $controller->handle('GET', "/lab-orders/{$orderId}", ['format' => 'html'], 'medico-1', 'hosp-a');
check('la View HTML lista las pruebas', is_string($html['body']) && str_contains($html['body'], '<td>GLU</td>'));
check('la View escapa el texto dinámico', is_string($html['body']) && str_contains($html['body'], 'Sodio &lt;b&gt;') && !str_contains($html['body'], 'Sodio <b>'));

check('PATCH a COLLECTED → 200', $controller->handle('PATCH', "/lab-orders/{$orderId}/status", ['status' => 'COLLECTED'], 'lab-1', 'hosp-a')['status'] === 200);
check('transición inválida → 409', $controller->handle('PATCH', "/lab-orders/{$orderId}/status", ['status' => 'VALIDATED'], 'lab-1', 'hosp-a')['status'] === 409);
$cancelled = $controller->handle('POST', "/lab-orders/{$orderId}/cancel", ['reason' => 'El paciente no requiere el estudio'], 'medico-1', 'hosp-a');
check('cancelación con motivo → 200 CANCELLED', $cancelled['status'] === 200 && $cancelled['body']['lab_order']['status'] === 'CANCELLED');

check('orden inexistente → 404', $controller->handle('GET', '/lab-orders/no-existe', [], 'medico-1', 'hosp-a')['status'] === 404);
check('ruta desconocida → 404', $controller->handle('DELETE', '/lab-orders', [], 'medico-1', 'hosp-a')['status'] === 404);
$list = $controller->handle('GET', '/lab-orders', ['status' => 'CANCELLED'], 'medico-1', 'hosp-a');
check('listado filtrado por estado → 1', $list['status'] === 200 && count($list['body']['lab_orders']) === 1);
check('per_page fuera de rango → 422', $controller->handle('GET', '/lab-orders', ['per_page' => 99], 'medico-1', 'hosp-a')['status'] === 422);

echo $failures === 0 ? "OK: verificaciones MVC aprobadas\n" : "FALLARON {$failures} verificaciones\n";
exit($failures === 0 ? 0 : 1);
