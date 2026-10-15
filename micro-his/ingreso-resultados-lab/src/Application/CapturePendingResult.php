<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Application;

final class CapturePendingResult
{
    public function __construct(
        private readonly PendingResultRepository $results,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @return array<string, string|int|null> */
    public function execute(CaptureResultCommand $command): array
    {
        $result = $this->results->getById($command->tenantId, $command->resultId);
        $result->capture($command->value, $command->unit, $command->observation);

        $this->results->save($result);
        $this->audit->record([
            'tenant_id' => $command->tenantId,
            'result_id' => $command->resultId,
            'action' => 'capture',
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
