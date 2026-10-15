<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Application;

use MicroHis\NativePhpPrototype\Domain\LocalHisAction;

interface LocalActionRepository
{
    public function save(LocalHisAction $action): void;

    /** @return LocalHisAction[] */
    public function pendingForTenant(string $tenantId): array;
}
