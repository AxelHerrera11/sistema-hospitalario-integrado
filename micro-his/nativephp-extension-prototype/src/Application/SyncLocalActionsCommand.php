<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Application;

use MicroHis\NativePhpPrototype\Domain\ConnectivityStatus;

final class SyncLocalActionsCommand
{
    public function __construct(
        public readonly string $tenantId,
        public readonly ConnectivityStatus $connectivity,
    ) {
    }
}
