<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Application;

final class ValidateReviewedResult
{
    public function __construct(
        private readonly ReviewResultRepository $results,
        private readonly ReviewAuditLogger $audit,
    ) {
    }

    /** @return array<string, string|int|null> */
    public function execute(ValidateResultCommand $command): array
    {
        $result = $this->results->getPending($command->tenantId, $command->resultId);
        $result->validate($command->role);

        $this->results->saveWithAudit($result, [
            'tenant_id' => $command->tenantId,
            'result_id' => $command->resultId,
            'action' => 'validate',
            'user_id' => $command->userId,
            'version' => $result->version,
        ], $this->audit);

        return [
            'id' => $result->id,
            'status' => $result->status->value,
            'version' => $result->version,
        ];
    }
}
