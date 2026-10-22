<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Application;

final class RejectResultCommand
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $resultId,
        public readonly string $reason,
        public readonly string $userId,
        public readonly string $role,
    ) {
    }
}
