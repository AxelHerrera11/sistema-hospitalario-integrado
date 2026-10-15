<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Application;

final class CorrectCapturedResult
{
    public function __construct(
        private readonly PendingResultRepository $results,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @return array<string, string|int|null> */
    public function execute(CorrectionCommand $command): array
    {
        $result = $this->results->getById($command->tenantId, $command->resultId);
        $result->correct($command->value, $command->unit, $command->reason);

        $this->results->save($result);
        $this->audit->record([
            'tenant_id' => $command->tenantId,
            'result_id' => $command->resultId,
            'action' => 'correction',
            'reason' => $command->reason,
            'user_id' => $command->userId,
            'version' => $result->version,
        ]);

        return [
            'id' => $result->id,
            'status' => $result->status->value,
            'version' => $result->version,
        ];
    }
}
