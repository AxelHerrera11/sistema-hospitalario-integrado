<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Persistence;

use MicroHis\ValidacionResultados\Application\ReviewResultRepository;
use MicroHis\ValidacionResultados\Domain\ReviewableLabResult;
use MicroHis\ValidacionResultados\Domain\ReviewStatus;
use PDO;

final class PdoReviewResultRepository implements ReviewResultRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getPending(string $tenantId, string $resultId): ReviewableLabResult
    {
        $statement = $this->pdo->prepare(
            'select id, tenant_id, patient_code, test_code, value, unit, expected_unit, status, decision_reason, version
             from lab_results_for_review
             where tenant_id = :tenant_id and id = :id'
        );
        $statement->execute([
            ':tenant_id' => $tenantId,
            ':id' => $resultId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new \RuntimeException('Resultado en revision no encontrado.');
        }

        return new ReviewableLabResult(
            id: (string) $row['id'],
            tenantId: (string) $row['tenant_id'],
            patientCode: (string) $row['patient_code'],
            testCode: (string) $row['test_code'],
            value: (string) $row['value'],
            unit: (string) $row['unit'],
            expectedUnit: (string) $row['expected_unit'],
            status: ReviewStatus::from((string) $row['status']),
            decisionReason: $row['decision_reason'] !== null ? (string) $row['decision_reason'] : null,
            version: (int) $row['version'],
        );
    }

    public function save(ReviewableLabResult $result): void
    {
        $statement = $this->pdo->prepare(
            'update lab_results_for_review
             set status = :status, decision_reason = :decision_reason, version = :version
             where tenant_id = :tenant_id and id = :id'
        );
        $statement->execute([
            ':status' => $result->status->value,
            ':decision_reason' => $result->decisionReason,
            ':version' => $result->version,
            ':tenant_id' => $result->tenantId,
            ':id' => $result->id,
        ]);
    }
}
