<?php

declare(strict_types=1);

use MicroHis\IngresoResultados\Application\AuditLogger;
use MicroHis\IngresoResultados\Application\CapturePendingResult;
use MicroHis\IngresoResultados\Application\CaptureResultCommand;
use MicroHis\IngresoResultados\Application\CorrectCapturedResult;
use MicroHis\IngresoResultados\Application\CorrectionCommand;
use MicroHis\IngresoResultados\Application\PendingResultNotFound;
use MicroHis\IngresoResultados\Application\PendingResultRepository;
use MicroHis\IngresoResultados\Domain\DomainRuleViolation;
use MicroHis\IngresoResultados\Domain\PendingLabResult;
use MicroHis\IngresoResultados\Persistence\ArrayAuditLogger;
use MicroHis\IngresoResultados\Persistence\InMemoryPendingResultRepository;
use MicroHis\IngresoResultados\Persistence\PdoPendingResultRepository;
use MicroHis\IngresoResultados\Presentation\HttpResultController;

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

$audit = new ArrayAuditLogger();
$repository = new InMemoryPendingResultRepository([
    new PendingLabResult('res-001', 'hospital-a', 'PAC-001', 'GLU', 'mg/dL'),
]);
$useCase = new CapturePendingResult($repository, $audit);
$response = $useCase->execute(new CaptureResultCommand(
    tenantId: 'hospital-a',
    resultId: 'res-001',
    value: '95',
    unit: 'mg/dL',
    observation: 'Muestra sin hemolisis',
    userId: 'tech-01',
));
assertTrue($response['status'] === 'capturado', 'Camino feliz debe capturar resultado.');
assertTrue(count($audit->events) === 1, 'Camino feliz debe auditar captura.');
assertTrue($audit->events[0]['new_value'] === '95', 'La auditoria debe conservar el valor nuevo.');

$isolatedRepository = new InMemoryPendingResultRepository([
    new PendingLabResult('res-isolated', 'hospital-a', 'PAC-000', 'GLU', 'mg/dL'),
]);
$detached = $isolatedRepository->getById('hospital-a', 'res-isolated');
$detached->capture('80', 'mg/dL', null);
assertTrue($isolatedRepository->getById('hospital-a', 'res-isolated')->status->value === 'pendiente', 'Leer y mutar no debe cambiar lo almacenado sin save().');

$repository = new InMemoryPendingResultRepository([
    new PendingLabResult('res-002', 'hospital-a', 'PAC-002', 'HB', 'g/dL'),
]);
$useCase = new CapturePendingResult($repository, new ArrayAuditLogger());
expectException(DomainRuleViolation::class, fn () => $useCase->execute(new CaptureResultCommand(
    tenantId: 'hospital-a',
    resultId: 'res-002',
    value: '13.2',
    unit: 'mg/dL',
    observation: null,
    userId: 'tech-01',
)));

$captured = new PendingLabResult('res-003', 'hospital-a', 'PAC-003', 'GLU', 'mg/dL');
$captured->capture('90', 'mg/dL', null);
$correction = new CorrectCapturedResult(
    $correctionRepository = new InMemoryPendingResultRepository([$captured]),
    $correctionAudit = new ArrayAuditLogger(),
);
expectException(DomainRuleViolation::class, fn () => $correction->execute(new CorrectionCommand(
    tenantId: 'hospital-a',
    resultId: 'res-003',
    value: '91',
    unit: 'mg/dL',
    reason: '',
    userId: 'tech-02',
)));

$correction->execute(new CorrectionCommand('hospital-a', 'res-003', '91', 'mg/dL', 'Ajuste validado', 'tech-02'));
assertTrue($correctionAudit->events[0]['previous_value'] === '90', 'La auditoria debe conservar el valor anterior.');
assertTrue($correctionAudit->events[0]['new_value'] === '91', 'La auditoria debe conservar el valor corregido.');

$rollbackResult = new PendingLabResult('res-rollback', 'hospital-a', 'PAC-005', 'GLU', 'mg/dL');
$rollbackResult->capture('90', 'mg/dL', null);
$rollbackRepository = new InMemoryPendingResultRepository([$rollbackResult]);
$failingAudit = new class implements AuditLogger {
    public function record(array $event): void
    {
        throw new RuntimeException('Falla simulada de auditoria.');
    }
};
expectException(RuntimeException::class, fn () => (new CorrectCapturedResult(
    $rollbackRepository,
    $failingAudit,
))->execute(new CorrectionCommand('hospital-a', 'res-rollback', '91', 'mg/dL', 'Prueba atomica', 'tech-02')));
assertTrue($rollbackRepository->getById('hospital-a', 'res-rollback')->value === '90', 'La auditoria fallida debe revertir el cambio.');

$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE lab_pending_results (id TEXT, tenant_id TEXT, patient_code TEXT, test_code TEXT, expected_unit TEXT, status TEXT, value TEXT, unit TEXT, observation TEXT, version INTEGER, PRIMARY KEY (id, tenant_id))');
$missing = new PendingLabResult('res-missing', 'hospital-a', 'PAC-006', 'GLU', 'mg/dL');
$missing->capture('92', 'mg/dL', null);
expectException(PendingResultNotFound::class, fn () => (new PdoPendingResultRepository($pdo))->save($missing));

$failingRepository = new class implements PendingResultRepository {
    public function getById(string $tenantId, string $resultId): PendingLabResult
    {
        return new PendingLabResult($resultId, $tenantId, 'PAC-004', 'GLU', 'mg/dL');
    }

    public function save(PendingLabResult $result): void
    {
        throw new RuntimeException('Falla simulada de persistencia.');
    }

    public function saveWithAudit(PendingLabResult $result, array $event, AuditLogger $audit): void
    {
        throw new RuntimeException('Falla simulada de persistencia.');
    }
};
$useCase = new CapturePendingResult($failingRepository, new class implements AuditLogger {
    public function record(array $event): void
    {
    }
});
expectException(RuntimeException::class, fn () => $useCase->execute(new CaptureResultCommand(
    tenantId: 'hospital-a',
    resultId: 'res-004',
    value: '88',
    unit: 'mg/dL',
    observation: null,
    userId: 'tech-03',
)));

$notFoundHttp = new HttpResultController(new CapturePendingResult(new InMemoryPendingResultRepository(), new ArrayAuditLogger()));
assertTrue($notFoundHttp->capture(['tenant_id' => 'hospital-a', 'result_id' => 'missing'])['status'] === 404, 'El controlador debe devolver 404 para un resultado inexistente.');
$failureHttp = new HttpResultController(new CapturePendingResult($failingRepository, new ArrayAuditLogger()));
assertTrue($failureHttp->capture(['tenant_id' => 'hospital-a', 'result_id' => 'res-004', 'value' => '88', 'unit' => 'mg/dL'])['status'] === 500, 'El controlador debe conservar 500 para una falla interna.');
$_POST = ['tenant_id' => 'hospital-a', 'result_id' => 'missing'];
ob_start();
require __DIR__ . '/../public/index.php';
ob_end_clean();
assertTrue(http_response_code() === 404, 'El adaptador HTTP debe aplicar el estado 404 real.');

echo "OK: camino feliz, regla de dominio y error de persistencia verificados." . PHP_EOL;
