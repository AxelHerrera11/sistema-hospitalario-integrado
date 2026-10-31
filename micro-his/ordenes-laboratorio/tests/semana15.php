<?php

declare(strict_types=1);

use MicroHis\OrdenesLab\Application\AdvanceLabOrder;
use MicroHis\OrdenesLab\Application\CancelLabOrder;
use MicroHis\OrdenesLab\Application\FindLabOrder;
use MicroHis\OrdenesLab\Application\IssueLabOrder;
use MicroHis\OrdenesLab\Application\ListLabOrders;
use MicroHis\OrdenesLab\Application\PersistenceFailure;
use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Persistence\InMemoryLabOrderRepository;
use MicroHis\OrdenesLab\Presentation\ApiKernel;
use MicroHis\OrdenesLab\Presentation\LabOrderController;
use MicroHis\OrdenesLab\Presentation\LabOrderPresenter;
use MicroHis\OrdenesLab\Presentation\LabOrderView;
use MicroHis\OrdenesLab\Tests\Fixtures\InMemoryExpedienteGateway;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/InMemoryExpedienteGateway.php';

$failures = 0;
$total = 0;

function check(string $id, string $label, bool $condition): void
{
    global $failures, $total;
    $total++;
    echo ($condition ? 'PASS' : 'FAIL') . "  {$id}  {$label}\n";
    $failures += $condition ? 0 : 1;
}

function draft(string $key, array $overrides = []): array
{
    return array_replace([
        'local_patient_id' => 'pat-1',
        'care_episode_id' => 'epi-1',
        'clinical_note_id' => 'note-1',
        'priority' => 'URGENT',
        'clinical_context' => 'Control metabólico',
        'idempotency_key' => $key,
        'items' => [['lab_test_id' => 'test-glu'], ['lab_test_id' => 'test-hb']],
    ], $overrides);
}

function buildApp(): array
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
    $repo = new InMemoryLabOrderRepository();
    $sequence = 0;
    $next = static function () use (&$sequence): string {
        return 'sem15-' . (++$sequence);
    };
    $now = static fn (): string => '2026-10-22T12:00:00-06:00';

    $issue = new IssueLabOrder($repo, $gateway, $next, $next, $now, $next);

    return [
        'repo' => $repo,
        'issue' => $issue,
        'advance' => new AdvanceLabOrder($repo, $next, $now),
        'cancel' => new CancelLabOrder($repo, $next, $now),
        'find' => new FindLabOrder($repo),
        'list' => new ListLabOrders($repo),
    ];
}

function buildKernel(array $app): ApiKernel
{
    return new ApiKernel(
        new LabOrderController(
            $app['issue'],
            $app['advance'],
            $app['cancel'],
            $app['find'],
            $app['list'],
            new LabOrderPresenter(),
            new LabOrderView(),
        ),
        static fn (): string => 'req-sem15',
    );
}

function errorCode(callable $operation): ?string
{
    try {
        $operation();
    } catch (DomainRuleViolation $error) {
        return $error->error;
    } catch (PersistenceFailure) {
        return 'PERSISTENCE_FAILURE';
    } catch (Throwable $error) {
        return get_class($error) . ': ' . $error->getMessage();
    }

    return null;
}

echo "== Unitarias (dominio) ==\n";
$unit = buildApp();
$order = $unit['issue']->execute(draft('u-1'), 'medico-1', 'hosp-a');
check('U1', 'emisión válida nace ISSUED con dos ítems y evento inicial', $order->status === 'ISSUED' && count($order->items) === 2 && count($order->events) === 1);
check('U2', 'prueba repetida se rechaza con ORDER_ITEMS_INVALID', errorCode(fn () => $unit['issue']->execute(draft('u-2', ['items' => [['lab_test_id' => 'test-glu'], ['lab_test_id' => 'test-glu']]]), 'medico-1', 'hosp-a')) === 'ORDER_ITEMS_INVALID');
check('U3', 'salto ISSUED → VALIDATED se rechaza con INVALID_STATUS_TRANSITION', errorCode(fn () => $unit['advance']->execute('hosp-a', $order->id, 'VALIDATED', 'lab-1')) === 'INVALID_STATUS_TRANSITION');
$cancelable = $unit['issue']->execute(draft('u-3'), 'medico-1', 'hosp-a');
$cancelled = $unit['cancel']->execute('hosp-a', $cancelable->id, 'medico-1', 'Estudio duplicado');
$lastEvent = $cancelled->events[count($cancelled->events) - 1];
check('U4', 'cancelación válida guarda estado, actor y motivo', $cancelled->status === 'CANCELLED' && $lastEvent->actor === 'medico-1' && $lastEvent->reason === 'Estudio duplicado');
$validated = $unit['issue']->execute(draft('u-4'), 'medico-1', 'hosp-a');
foreach (['COLLECTED', 'IN_PROCESS', 'RESULTED', 'VALIDATED'] as $status) {
    $unit['advance']->execute('hosp-a', $validated->id, $status, 'lab-1');
}
check('U5', 'cancelar una orden validada se rechaza con ORDER_ALREADY_VALIDATED', errorCode(fn () => $unit['cancel']->execute('hosp-a', $validated->id, 'medico-1', 'ya validada')) === 'ORDER_ALREADY_VALIDATED');
check('U6', 'cancelar sin motivo se rechaza con CANCELLATION_REASON_REQUIRED', errorCode(fn () => $unit['cancel']->execute('hosp-a', $order->id, 'medico-1', '   ')) === 'CANCELLATION_REASON_REQUIRED');
check('U7', 'episodio cerrado se rechaza con CARE_EPISODE_NOT_OPEN', errorCode(fn () => $unit['issue']->execute(draft('u-7', ['care_episode_id' => 'epi-closed']), 'medico-1', 'hosp-a')) === 'CARE_EPISODE_NOT_OPEN');
check('U8', 'prueba inactiva o inexistente se rechaza con LAB_TEST_NOT_ACTIVE', errorCode(fn () => $unit['issue']->execute(draft('u-8', ['items' => [['lab_test_id' => 'test-unknown']]]), 'medico-1', 'hosp-a')) === 'LAB_TEST_NOT_ACTIVE');
$found = $unit['find']->execute('hosp-a', $order->id);
$listed = $unit['list']->execute('hosp-a', ['status' => 'ISSUED']);
check('U9', 'consulta devuelve ítems y eventos de la orden', $found->id === $order->id && count($found->items) === 2 && count($found->events) === 1);
check('U10', 'listado por estado solo devuelve órdenes ISSUED', count($listed['items']) === 1 && $listed['items'][0]->status === 'ISSUED');

echo "\n== Caja blanca (ramas y condiciones) ==\n";
$white = buildApp();
$first = $white['issue']->execute(draft('w-1'), 'medico-1', 'hosp-a');
$again = $white['issue']->execute(draft('w-1'), 'medico-1', 'hosp-a');
$countAfterReplay = count($white['list']->execute('hosp-a', [])['items']);
check('W1', 'rama de idempotencia: misma clave retorna la orden existente sin duplicar', $again->id === $first->id && $countAfterReplay === 1);
$before = count($white['list']->execute('hosp-a', [])['items']);
$white['repo']->failNextCreate = true;
$rollbackCode = errorCode(fn () => $white['issue']->execute(draft('w-2'), 'medico-1', 'hosp-a'));
$after = count($white['list']->execute('hosp-a', [])['items']);
check('W2', 'rama de fallo: PersistenceFailure revierte y no deja orden parcial', $rollbackCode === 'PERSISTENCE_FAILURE' && $after === $before);
$short = $white['issue']->execute(draft('w-3'), 'medico-1', 'hosp-a');
$long = $white['issue']->execute(draft('w-4'), 'medico-1', 'hosp-a');
$exact = $white['issue']->execute(draft('w-5'), 'medico-1', 'hosp-a');
$max = $white['issue']->execute(draft('w-6'), 'medico-1', 'hosp-a');
$shortCode = errorCode(fn () => $white['cancel']->execute('hosp-a', $short->id, 'medico-1', 'abcd'));
$longCode = errorCode(fn () => $white['cancel']->execute('hosp-a', $long->id, 'medico-1', str_repeat('a', 251)));
$exactOk = $white['cancel']->execute('hosp-a', $exact->id, 'medico-1', 'abcde')->status === 'CANCELLED';
$maxOk = $white['cancel']->execute('hosp-a', $max->id, 'medico-1', str_repeat('b', 250))->status === 'CANCELLED';
check('W3', 'condiciones de longitud: 4 y 251 fallan; 5 y 250 se aceptan', $shortCode === 'CANCELLATION_REASON_INVALID' && $longCode === 'CANCELLATION_REASON_INVALID' && $exactOk && $maxOk);

echo "\n== Caja negra (API) ==\n";
$black = buildApp();
$kernel = buildKernel($black);
$permissions = 'laboratorio.ordenes.crear,laboratorio.ordenes.ver,laboratorio.ordenes.actualizar,laboratorio.ordenes.cancelar';
$auth = ['X-Tenant-ID' => 'hosp-a', 'X-User-Ref' => 'medico-1', 'X-Permissions' => $permissions];
$created = $kernel->handle('POST', '/api/v1/lab-orders', $auth, draft('b-1'));
$orderId = (string) ($created['body']['lab_order']['lab_order_id'] ?? '');
check('B1', 'emisión válida por API devuelve 201', $created['status'] === 201 && $orderId !== '');
$replay = $kernel->handle('POST', '/api/v1/lab-orders', $auth, draft('b-1'));
check('B2', 'replay con la misma clave devuelve 200 y la misma orden', $replay['status'] === 200 && ($replay['body']['lab_order']['lab_order_id'] ?? null) === $orderId);
$noCreate = $kernel->handle('POST', '/api/v1/lab-orders', ['X-Tenant-ID' => 'hosp-a', 'X-User-Ref' => 'auditor-1', 'X-Permissions' => 'laboratorio.ordenes.ver'], draft('b-2'));
check('B3', 'sin permiso de creación devuelve 403', $noCreate['status'] === 403 && ($noCreate['body']['code'] ?? '') === 'FORBIDDEN');
$noView = $kernel->handle('GET', "/api/v1/lab-orders/{$orderId}", ['X-Tenant-ID' => 'hosp-a', 'X-User-Ref' => 'auditor-1', 'X-Permissions' => 'laboratorio.ordenes.crear'], []);
check('B4', 'sin permiso de lectura devuelve 403', $noView['status'] === 403);
$otherTenant = $kernel->handle('GET', "/api/v1/lab-orders/{$orderId}", ['X-Tenant-ID' => 'hosp-b', 'X-User-Ref' => 'medico-1', 'X-Permissions' => 'laboratorio.ordenes.ver'], []);
check('B5', 'otro tenant recibe 404 sin revelar la orden', $otherTenant['status'] === 404);
$shortReason = $kernel->handle('POST', "/api/v1/lab-orders/{$orderId}/cancel", $auth, ['reason' => 'abcd']);
$longReason = $kernel->handle('POST', "/api/v1/lab-orders/{$orderId}/cancel", $auth, ['reason' => str_repeat('a', 251)]);
$validReason = $kernel->handle('POST', "/api/v1/lab-orders/{$orderId}/cancel", $auth, ['reason' => 'abcde']);
check('B6', 'límites del motivo: 4 y 251 → 422; 5 → 200 CANCELLED', $shortReason['status'] === 422 && ($shortReason['body']['code'] ?? '') === 'CANCELLATION_REASON_INVALID' && $longReason['status'] === 422 && $validReason['status'] === 200 && ($validReason['body']['lab_order']['status'] ?? '') === 'CANCELLED');
$pageOne = $kernel->handle('GET', '/api/v1/lab-orders', $auth, ['per_page' => 1]);
$pageMax = $kernel->handle('GET', '/api/v1/lab-orders', $auth, ['per_page' => 50]);
$pageZero = $kernel->handle('GET', '/api/v1/lab-orders', $auth, ['per_page' => 0]);
$pageTooHigh = $kernel->handle('GET', '/api/v1/lab-orders', $auth, ['per_page' => 51]);
check('B7', 'paginación: 1 y 50 válidos; 0 y 51 → 422', $pageOne['status'] === 200 && $pageMax['status'] === 200 && $pageZero['status'] === 422 && $pageTooHigh['status'] === 422);
$missingKey = $kernel->handle('POST', '/api/v1/lab-orders', $auth, array_diff_key(draft('b-3'), ['idempotency_key' => true]));
check('B8', 'sin Idempotency-Key devuelve 422', $missingKey['status'] === 422 && ($missingKey['body']['code'] ?? '') === 'IDEMPOTENCY_KEY_REQUIRED');
$unknownPath = $kernel->handle('GET', '/api/v9/lab-orders', $auth, []);
$unknownMethod = $kernel->handle('DELETE', '/api/v1/lab-orders', $auth, []);
check('B9', 'ruta y método desconocidos devuelven 404', $unknownPath['status'] === 404 && $unknownMethod['status'] === 404);
$problem = $kernel->handle('GET', '/api/v1/lab-orders', [], []);
check('B10', 'error sin tenant usa problem+json y request_id', $problem['status'] === 400 && str_contains($problem['headers']['Content-Type'], 'application/problem+json') && ($problem['body']['request_id'] ?? '') === 'req-sem15');
$failureApp = buildApp();
$failureApp['repo']->failNextCreate = true;
$failure = buildKernel($failureApp)->handle('POST', '/api/v1/lab-orders', $auth, draft('b-4'));
check('B11', 'fallo de persistencia responde 503 PERSISTENCE_FAILURE sin traza', $failure['status'] === 503 && ($failure['body']['code'] ?? '') === 'PERSISTENCE_FAILURE' && str_contains($failure['headers']['Content-Type'], 'application/problem+json'));

echo "\n" . ($failures === 0 ? "OK: {$total} verificaciones de la semana 15 aprobadas\n" : "FALLARON {$failures} de {$total} verificaciones\n");
exit($failures === 0 ? 0 : 1);
