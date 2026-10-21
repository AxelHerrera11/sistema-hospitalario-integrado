<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Persistence;

use MicroHis\IngresoResultados\Application\PendingResultRepository;
use MicroHis\IngresoResultados\Application\AuditLogger;
use MicroHis\IngresoResultados\Application\PendingResultNotFound;
use MicroHis\IngresoResultados\Domain\PendingLabResult;
use MicroHis\IngresoResultados\Domain\ResultStatus;
use PDO;

final class PdoPendingResultRepository implements PendingResultRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getById(string $tenantId, string $resultId): PendingLabResult
    {
        $statement = $this->pdo->prepare(
            'select id, tenant_id, patient_code, test_code, expected_unit, status, value, unit, observation, version
             from lab_pending_results
             where tenant_id = :tenant_id and id = :id'
        );
        $statement->execute([
            ':tenant_id' => $tenantId,
            ':id' => $resultId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new PendingResultNotFound('Resultado pendiente no encontrado.');
        }

        return new PendingLabResult(
            id: (string) $row['id'],
            tenantId: (string) $row['tenant_id'],
            patientCode: (string) $row['patient_code'],
            testCode: (string) $row['test_code'],
            expectedUnit: (string) $row['expected_unit'],
            status: ResultStatus::from((string) $row['status']),
            value: $row['value'] !== null ? (string) $row['value'] : null,
            unit: $row['unit'] !== null ? (string) $row['unit'] : null,
            observation: $row['observation'] !== null ? (string) $row['observation'] : null,
            version: (int) $row['version'],
        );
    }

    public function save(PendingLabResult $result): void
    {
        $statement = $this->pdo->prepare(
            'update lab_pending_results
             set status = :status, value = :value, unit = :unit, observation = :observation, version = :version
             where tenant_id = :tenant_id and id = :id'
        );
        $statement->execute([
            ':status' => $result->status->value,
            ':value' => $result->value,
            ':unit' => $result->unit,
            ':observation' => $result->observation,
            ':version' => $result->version,
            ':tenant_id' => $result->tenantId,
            ':id' => $result->id,
        ]);

        if ($statement->rowCount() === 0) {
            throw new PendingResultNotFound('Resultado pendiente no encontrado.');
        }
    }

    public function saveWithAudit(PendingLabResult $result, array $event, AuditLogger $audit): void
    {
        $startedTransaction = !$this->pdo->inTransaction();
        if ($startedTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $this->save($result);
            $audit->record($event);
            if ($startedTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $error) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
