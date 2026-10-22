<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Application;

final class ValidateResultCommand
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $resultId,
        public readonly string $userId,
        public readonly string $role,
    ) {
    }
}
