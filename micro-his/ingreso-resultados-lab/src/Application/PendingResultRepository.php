<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Application;

use MicroHis\IngresoResultados\Domain\PendingLabResult;

interface PendingResultRepository
{
    public function getById(string $tenantId, string $resultId): PendingLabResult;

    public function save(PendingLabResult $result): void;
}
