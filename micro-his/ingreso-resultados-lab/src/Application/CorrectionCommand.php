<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Application;

final class CorrectionCommand
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $resultId,
        public readonly string $value,
        public readonly string $unit,
        public readonly string $reason,
        public readonly string $userId,
    ) {
    }
}
