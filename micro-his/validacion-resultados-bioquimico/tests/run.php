<?php

declare(strict_types=1);

use MicroHis\ValidacionResultados\Application\RejectReviewedResult;
use MicroHis\ValidacionResultados\Application\RejectResultCommand;
use MicroHis\ValidacionResultados\Application\ReviewAuditLogger;
use MicroHis\ValidacionResultados\Application\ReviewResultNotFound;
use MicroHis\ValidacionResultados\Application\ReviewResultRepository;
use MicroHis\ValidacionResultados\Application\ValidateReviewedResult;
use MicroHis\ValidacionResultados\Application\ValidateResultCommand;
use MicroHis\ValidacionResultados\Domain\DomainRuleViolation;
use MicroHis\ValidacionResultados\Domain\ReviewableLabResult;
use MicroHis\ValidacionResultados\Persistence\ArrayReviewAuditLogger;
use MicroHis\ValidacionResultados\Persistence\InMemoryReviewResultRepository;
use MicroHis\ValidacionResultados\Persistence\PdoReviewResultRepository;
use MicroHis\ValidacionResultados\Presentation\HttpReviewController;

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

$audit = new ArrayReviewAuditLogger();
$repository = new InMemoryReviewResultRepository([
    new ReviewableLabResult('rev-001', 'hospital-a', 'PAC-001', 'GLU', '95', 'mg/dL', 'mg/dL'),
]);
$useCase = new ValidateReviewedResult($repository, $audit);
$response = $useCase->execute(new ValidateResultCommand(
    tenantId: 'hospital-a',
    resultId: 'rev-001',
    userId: 'bio-01',
    role: 'bioquimico',
));
assertTrue($response['status'] === 'validado', 'Camino feliz debe validar resultado.');
assertTrue(count($audit->events) === 1, 'Camino feliz debe auditar validacion.');

$isolatedRepository = new InMemoryReviewResultRepository([
    new ReviewableLabResult('rev-isolated', 'hospital-a', 'PAC-000', 'GLU', '90', 'mg/dL', 'mg/dL'),
]);
$detached = $isolatedRepository->getPending('hospital-a', 'rev-isolated');
$detached->validate('bioquimico');
assertTrue($isolatedRepository->getPending('hospital-a', 'rev-isolated')->status->value === 'pendiente_revision', 'Leer y mutar no debe cambiar lo almacenado sin save().');

$rollbackRepository = new InMemoryReviewResultRepository([
    new ReviewableLabResult('rev-rollback', 'hospital-a', 'PAC-005', 'GLU', '90', 'mg/dL', 'mg/dL'),
]);
$failingAudit = new class implements ReviewAuditLogger {
    public function record(array $event): void
    {
        throw new RuntimeException('Falla simulada de auditoria.');
    }
};
expectException(RuntimeException::class, fn () => (new ValidateReviewedResult($rollbackRepository, $failingAudit))->execute(
    new ValidateResultCommand('hospital-a', 'rev-rollback', 'bio-04', 'bioquimico')
));
assertTrue($rollbackRepository->getPending('hospital-a', 'rev-rollback')->status->value === 'pendiente_revision', 'La auditoria fallida debe revertir el estado.');

$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE lab_results_for_review (id TEXT, tenant_id TEXT, patient_code TEXT, test_code TEXT, value TEXT, unit TEXT, expected_unit TEXT, status TEXT, decision_reason TEXT, version INTEGER, PRIMARY KEY (id, tenant_id))');
$missing = new ReviewableLabResult('rev-missing', 'hospital-a', 'PAC-006', 'GLU', '90', 'mg/dL', 'mg/dL');
$missing->validate('bioquimico');
expectException(ReviewResultNotFound::class, fn () => (new PdoReviewResultRepository($pdo))->save($missing));

$repository = new InMemoryReviewResultRepository([
    new ReviewableLabResult('rev-002', 'hospital-a', 'PAC-002', 'HB', '13.2', 'g/dL', 'g/dL'),
]);
$rejectUseCase = new RejectReviewedResult($repository, new ArrayReviewAuditLogger());
expectException(DomainRuleViolation::class, fn () => $rejectUseCase->execute(new RejectResultCommand(
    tenantId: 'hospital-a',
    resultId: 'rev-002',
    reason: '',
    userId: 'bio-01',
    role: 'bioquimico',
)));

$alreadyValidated = new ReviewableLabResult('rev-003', 'hospital-a', 'PAC-003', 'GLU', '100', 'mg/dL', 'mg/dL');
$alreadyValidated->validate('bioquimico');
$repository = new InMemoryReviewResultRepository([$alreadyValidated]);
$rejectUseCase = new RejectReviewedResult($repository, new ArrayReviewAuditLogger());
expectException(DomainRuleViolation::class, fn () => $rejectUseCase->execute(new RejectResultCommand(
    tenantId: 'hospital-a',
    resultId: 'rev-003',
    reason: 'Valor inconsistente',
    userId: 'bio-02',
    role: 'bioquimico',
)));

$failingRepository = new class implements ReviewResultRepository {
    public function getPending(string $tenantId, string $resultId): ReviewableLabResult
    {
        return new ReviewableLabResult($resultId, $tenantId, 'PAC-004', 'GLU', '88', 'mg/dL', 'mg/dL');
    }

    public function save(ReviewableLabResult $result): void
    {
        throw new RuntimeException('Falla simulada de persistencia.');
    }

    public function saveWithAudit(ReviewableLabResult $result, array $event, ReviewAuditLogger $audit): void
    {
        throw new RuntimeException('Falla simulada de persistencia.');
    }
};
$useCase = new ValidateReviewedResult($failingRepository, new class implements ReviewAuditLogger {
    public function record(array $event): void
    {
    }
});
expectException(RuntimeException::class, fn () => $useCase->execute(new ValidateResultCommand(
    tenantId: 'hospital-a',
    resultId: 'rev-004',
    userId: 'bio-03',
    role: 'bioquimico',
)));

$missingRepository = new InMemoryReviewResultRepository();
$notFoundHttp = new HttpReviewController(new ValidateReviewedResult($missingRepository, new ArrayReviewAuditLogger()), new RejectReviewedResult($missingRepository, new ArrayReviewAuditLogger()));
assertTrue($notFoundHttp->validate(['tenant_id' => 'hospital-a', 'result_id' => 'missing'])['status'] === 404, 'El controlador debe devolver 404 para un resultado inexistente.');
$_POST = ['tenant_id' => 'hospital-a', 'result_id' => 'missing', 'role' => 'bioquimico'];
ob_start();
require __DIR__ . '/../public/index.php';
ob_end_clean();
assertTrue(http_response_code() === 404, 'El adaptador HTTP debe aplicar el estado 404 real.');

echo "OK: validacion, reglas de dominio y error de persistencia verificados." . PHP_EOL;
