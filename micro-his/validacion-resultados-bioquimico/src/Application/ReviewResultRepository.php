<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Application;

use MicroHis\ValidacionResultados\Domain\ReviewableLabResult;

interface ReviewResultRepository
{
    public function getPending(string $tenantId, string $resultId): ReviewableLabResult;

    public function save(ReviewableLabResult $result): void;

    /** @param array<string, string|int|null> $event */
    public function saveWithAudit(ReviewableLabResult $result, array $event, ReviewAuditLogger $audit): void;
}
