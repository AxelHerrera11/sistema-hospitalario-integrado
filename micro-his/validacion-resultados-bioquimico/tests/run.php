<?php

declare(strict_types=1);

use MicroHis\ValidacionResultados\Application\RejectReviewedResult;
use MicroHis\ValidacionResultados\Application\RejectResultCommand;
use MicroHis\ValidacionResultados\Application\ReviewAuditLogger;
use MicroHis\ValidacionResultados\Application\ReviewResultRepository;
use MicroHis\ValidacionResultados\Application\ValidateReviewedResult;
use MicroHis\ValidacionResultados\Application\ValidateResultCommand;
use MicroHis\ValidacionResultados\Domain\DomainRuleViolation;
use MicroHis\ValidacionResultados\Domain\ReviewableLabResult;
use MicroHis\ValidacionResultados\Persistence\ArrayReviewAuditLogger;
use MicroHis\ValidacionResultados\Persistence\InMemoryReviewResultRepository;

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

echo "OK: validacion, reglas de dominio y error de persistencia verificados." . PHP_EOL;
