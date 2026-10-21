<?php

declare(strict_types=1);

use MicroHis\NativePhpPrototype\Application\LocalActionRepository;
use MicroHis\NativePhpPrototype\Application\PrepareLocalAction;
use MicroHis\NativePhpPrototype\Application\PrepareLocalActionCommand;
use MicroHis\NativePhpPrototype\Application\SyncLocalActions;
use MicroHis\NativePhpPrototype\Application\SyncLocalActionsCommand;
use MicroHis\NativePhpPrototype\Domain\ConnectivityStatus;
use MicroHis\NativePhpPrototype\Domain\DomainRuleViolation;
use MicroHis\NativePhpPrototype\Domain\LocalHisAction;
use MicroHis\NativePhpPrototype\Persistence\ArrayTelemetryRecorder;
use MicroHis\NativePhpPrototype\Persistence\FakeSyncGateway;
use MicroHis\NativePhpPrototype\Persistence\InMemoryLocalActionRepository;

require_once __DIR__ . '/../bootstrap.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectException(string $className, callable $callback): void
{
    try {
        $callback();
    } catch (Throwable $error) {
        assertTrue($error instanceof $className, 'Se esperaba excepcion ' . $className);
        return;
    }

    throw new RuntimeException('No se lanzo la excepcion esperada.');
}

$telemetry = new ArrayTelemetryRecorder();
$repository = new InMemoryLocalActionRepository();
$prepare = new PrepareLocalAction($repository, $telemetry);
$prepared = $prepare->execute(new PrepareLocalActionCommand(
    tenantId: 'hospital-a',
    actorId: 'user-01',
    kind: 'lab_result_preview',
    payload: '{"result_id":"res-001","value":"95"}',
));
assertTrue($prepared['status'] === 'pendiente_sincronizar', 'La accion debe quedar pendiente.');

$sync = new SyncLocalActions($repository, new FakeSyncGateway(true), $telemetry);
$summary = $sync->execute(new SyncLocalActionsCommand('hospital-a', ConnectivityStatus::Online));
assertTrue($summary['synced'] === 1, 'Debe sincronizar una accion.');
assertTrue(count($telemetry->events) === 2, 'Debe registrar preparacion y sync.');

$repository = new InMemoryLocalActionRepository([
    new LocalHisAction('local-001', 'hospital-a', 'user-02', 'lab_result_preview', '{"result_id":"res-002"}'),
]);
$sync = new SyncLocalActions($repository, new FakeSyncGateway(true), new ArrayTelemetryRecorder());
expectException(DomainRuleViolation::class, fn () => $sync->execute(
    new SyncLocalActionsCommand('hospital-a', ConnectivityStatus::Offline)
));

$failingRepository = new class implements LocalActionRepository {
    public function save(LocalHisAction $action): void
    {
        throw new RuntimeException('Falla simulada de persistencia local.');
    }

    public function update(LocalHisAction $action): void
    {
        throw new RuntimeException('Falla simulada de persistencia local.');
    }

    public function pendingForTenant(string $tenantId): array
    {
        return [];
    }
};
$prepare = new PrepareLocalAction($failingRepository, new ArrayTelemetryRecorder());
expectException(RuntimeException::class, fn () => $prepare->execute(new PrepareLocalActionCommand(
    tenantId: 'hospital-a',
    actorId: 'user-03',
    kind: 'lab_result_preview',
    payload: '{"result_id":"res-003"}',
)));

echo "OK: preparacion local, regla offline y error de persistencia verificados." . PHP_EOL;
