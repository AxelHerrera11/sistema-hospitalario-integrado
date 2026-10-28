<?php

declare(strict_types=1);

use MicroHis\OrdenesLab\Application\AdvanceLabOrder;
use MicroHis\OrdenesLab\Application\CancelLabOrder;
use MicroHis\OrdenesLab\Application\FindLabOrder;
use MicroHis\OrdenesLab\Application\IssueLabOrder;
use MicroHis\OrdenesLab\Application\LabOrderRepository;
use MicroHis\OrdenesLab\Application\ListLabOrders;
use MicroHis\OrdenesLab\Application\PersistenceFailure;
use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Persistence\InMemoryLabOrderRepository;
use MicroHis\OrdenesLab\Persistence\PdoLabOrderRepository;
use MicroHis\OrdenesLab\Presentation\LabOrderPresenter;
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

function failsWith(callable $operation, string $expected): bool
{
    try {
        $operation();
    } catch (DomainRuleViolation $error) {
        return $error->error === $expected;
    }

    return false;
}

/** @return array<string, mixed> */
function draft(string $key): array
{
    return [
        'local_patient_id' => 'pat-1',
        'care_episode_id' => 'epi-1',
        'clinical_note_id' => 'note-1',
        'priority' => 'URGENT',
        'clinical_context' => 'Control metabólico',
        'idempotency_key' => $key,
        'items' => [['lab_test_id' => 'test-glu'], ['lab_test_id' => 'test-hb']],
    ];
}

/** @return array{repo: LabOrderRepository, pdo: ?PDO, issue: IssueLabOrder, advance: AdvanceLabOrder, cancel: CancelLabOrder, find: FindLabOrder, list: ListLabOrders, presenter: LabOrderPresenter} */
function build(string $adapter): array
{
    $episodes = [
        'hosp-a|pat-1|epi-1' => ['patient_id' => 'pat-1', 'episode_id' => 'epi-1', 'open' => true, 'clinical_note_ids' => ['note-1']],
        'hosp-a|pat-1|epi-closed' => ['patient_id' => 'pat-1', 'episode_id' => 'epi-closed', 'open' => false, 'clinical_note_ids' => []],
    ];
    $tests = ['hosp-a' => [
        'test-glu' => ['code' => 'GLU', 'name' => 'Glucosa'],
        'test-hb' => ['code' => 'HB', 'name' => 'Hemograma'],
    ]];
    $gateway = new InMemoryExpedienteGateway($episodes, $tests);
    $pdo = null;
    if ($adapter === 'InMemory') {
        $repo = new InMemoryLabOrderRepository();
    } elseif ($adapter === 'PDO/PostgreSQL') {
        $pdo = new PDO((string) getenv('LAB_DB_DSN'), (string) getenv('LAB_DB_USER'), (string) getenv('LAB_DB_PASSWORD'));
        $pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
        $repo = new PdoLabOrderRepository($pdo);
    } else {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
        $repo = new PdoLabOrderRepository($pdo);
    }
    $sequence = 0;
    $next = static function () use (&$sequence): string {
        return 'id-' . (++$sequence);
    };
    $now = static fn (): string => '2026-10-22T10:00:00-06:00';
    $correlation = static function () use (&$sequence): string {
        return 'corr-' . $sequence;
    };

    return [
        'repo' => $repo,
        'pdo' => $pdo,
        'issue' => new IssueLabOrder($repo, $gateway, $next, $next, $now, $correlation),
        'advance' => new AdvanceLabOrder($repo, $next, $now),
        'cancel' => new CancelLabOrder($repo, $next, $now),
        'find' => new FindLabOrder($repo),
        'list' => new ListLabOrders($repo),
        'presenter' => new LabOrderPresenter(),
    ];
}

$adapters = ['InMemory', 'PDO/SQLite'];
if (getenv('LAB_DB_DSN') !== false && getenv('LAB_DB_DSN') !== '') {
    $adapters[] = 'PDO/PostgreSQL';
}
foreach ($adapters as $adapter) {
    echo "== {$adapter} ==\n";
    $app = build($adapter);
    $tenant = 'hosp-a';

    $order = $app['issue']->execute(draft('key-1'), 'medico-1', $tenant);
    check('emisión válida nace ISSUED', $order->status === 'ISSUED');
    check('dos ítems con instantánea del catálogo', count($order->items) === 2 && $order->items[0]->code === 'GLU');
    check('evento inicial de trazabilidad', count($order->events) === 1 && $order->events[0]->toStatus === 'ISSUED');
    check('correlation_id presente', $order->correlationId !== '');

    $again = $app['issue']->execute(draft('key-1'), 'medico-1', $tenant);
    check('la idempotencia devuelve la misma orden', $again->id === $order->id);
    check('no se duplica la orden', count($app['list']->execute($tenant, ['patient_id' => 'pat-1'])['items']) === 1);

    check('ítems vacíos → ORDER_ITEMS_INVALID', failsWith(fn () => $app['issue']->execute([...draft('key-2'), 'items' => []], 'medico-1', $tenant), 'ORDER_ITEMS_INVALID'));
    check('prueba repetida → ORDER_ITEMS_INVALID', failsWith(fn () => $app['issue']->execute([...draft('key-3'), 'items' => [['lab_test_id' => 'test-glu'], ['lab_test_id' => 'test-glu']]], 'medico-1', $tenant), 'ORDER_ITEMS_INVALID'));
    check('episodio cerrado → CARE_EPISODE_NOT_OPEN', failsWith(fn () => $app['issue']->execute([...draft('key-4'), 'care_episode_id' => 'epi-closed'], 'medico-1', $tenant), 'CARE_EPISODE_NOT_OPEN'));
    check('nota de otro paciente → CLINICAL_NOTE_MISMATCH', failsWith(fn () => $app['issue']->execute([...draft('key-5'), 'clinical_note_id' => 'note-x'], 'medico-1', $tenant), 'CLINICAL_NOTE_MISMATCH'));
    check('prueba inactiva → LAB_TEST_NOT_ACTIVE', failsWith(fn () => $app['issue']->execute([...draft('key-6'), 'items' => [['lab_test_id' => 'test-off']]], 'medico-1', $tenant), 'LAB_TEST_NOT_ACTIVE'));
    check('prioridad inválida → PRIORITY_UNSUPPORTED', failsWith(fn () => $app['issue']->execute([...draft('key-7'), 'priority' => 'CUANDO'], 'medico-1', $tenant), 'PRIORITY_UNSUPPORTED'));

    $advanced = $app['advance']->execute($tenant, $order->id, 'COLLECTED', 'lab-1');
    $advanced = $app['advance']->execute($tenant, $order->id, 'IN_PROCESS', 'lab-1');
    $advanced = $app['advance']->execute($tenant, $order->id, 'RESULTED', 'lab-1');
    $validated = $app['advance']->execute($tenant, $order->id, 'VALIDATED', 'lab-1');
    check('transición completa hasta VALIDATED', $validated->status === 'VALIDATED' && count($validated->events) === 5);
    check('historial conserva el actor de cada evento', $validated->events[1]->actor === 'lab-1');

    $fresh = $app['issue']->execute(draft('key-8'), 'medico-1', $tenant);
    check('transición no permitida → INVALID_STATUS_TRANSITION', failsWith(fn () => $app['advance']->execute($tenant, $fresh->id, 'VALIDATED', 'lab-1'), 'INVALID_STATUS_TRANSITION'));
    check('cancelar sin motivo → CANCELLATION_REASON_REQUIRED', failsWith(fn () => $app['cancel']->execute($tenant, $fresh->id, 'medico-1', '  '), 'CANCELLATION_REASON_REQUIRED'));
    $app['advance']->execute($tenant, $fresh->id, 'COLLECTED', 'lab-1');
    $app['advance']->execute($tenant, $fresh->id, 'IN_PROCESS', 'lab-1');
    $cancelled = $app['cancel']->execute($tenant, $fresh->id, 'medico-1', 'El paciente no requiere el estudio');
    check('cancelación guarda estado, actor y motivo', $cancelled->status === 'CANCELLED'
        && $cancelled->events[count($cancelled->events) - 1]->reason === 'El paciente no requiere el estudio');
    check('cancelar una orden validada → ORDER_ALREADY_VALIDATED', failsWith(fn () => $app['cancel']->execute($tenant, $order->id, 'medico-1', 'ya validada'), 'ORDER_ALREADY_VALIDATED'));

    check('otro hospital no ve la orden → ORDER_NOT_FOUND', failsWith(fn () => $app['find']->execute('hosp-b', $order->id), 'ORDER_NOT_FOUND'));
    check('listado por estado', count($app['list']->execute($tenant, ['status' => 'validated'])['items']) === 1);
    check('per_page fuera de rango → PAGINATION_INVALID', failsWith(fn () => $app['list']->execute($tenant, [], 51), 'PAGINATION_INVALID'));

    if ($adapter === 'InMemory') {
        $before = count($app['list']->execute($tenant, [], 50)['items']);
        $app['repo']->failNextCreate = true;
        try {
            $app['issue']->execute(draft('key-9'), 'medico-1', $tenant);
        } catch (PersistenceFailure) {
        }
        check('fallo de persistencia no deja orden a medias', count($app['list']->execute($tenant, [], 50)['items']) === $before);
    } else {
        $before = (int) $app['pdo']?->query('SELECT COUNT(*) FROM lab_orders')->fetchColumn();
        $app['pdo']?->exec('DROP TABLE lab_order_items');
        try {
            $app['issue']->execute(draft('key-9'), 'medico-1', $tenant);
        } catch (PersistenceFailure) {
        }
        $after = (int) $app['pdo']?->query('SELECT COUNT(*) FROM lab_orders')->fetchColumn();
        check('fallo de persistencia no deja orden a medias', $after === $before);
    }

    $presenter = $app['presenter'];
    check('el presentador mapea una orden creada a 201', $presenter->created($order)['status'] === 201);
    check('el presentador mapea un error de dominio a 422', $presenter->error(new DomainRuleViolation('ORDER_ITEMS_INVALID', 'items'))['status'] === 422);
    check('el presentador mapea una caída a 503', $presenter->error(new PersistenceFailure())['status'] === 503);
}

echo $failures === 0 ? "OK: todas las verificaciones aprobadas\n" : "FALLARON {$failures} verificaciones\n";
exit($failures === 0 ? 0 : 1);
