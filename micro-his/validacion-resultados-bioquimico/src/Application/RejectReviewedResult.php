<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Application;

final class RejectReviewedResult
{
    public function __construct(
        private readonly ReviewResultRepository $results,
        private readonly ReviewAuditLogger $audit,
    ) {
    }

    /** @return array<string, string|int|null> */
    public function execute(RejectResultCommand $command): array
    {
        $result = $this->results->getPending($command->tenantId, $command->resultId);
        $result->reject($command->role, $command->reason);

        $this->results->save($result);
        $this->audit->record([
            'tenant_id' => $command->tenantId,
            'result_id' => $command->resultId,
            'action' => 'reject',
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
