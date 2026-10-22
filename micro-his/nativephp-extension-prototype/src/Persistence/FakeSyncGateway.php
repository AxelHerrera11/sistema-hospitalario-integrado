<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Persistence;

use MicroHis\NativePhpPrototype\Application\SyncGateway;
use MicroHis\NativePhpPrototype\Domain\LocalHisAction;

final class FakeSyncGateway implements SyncGateway
{
    public function __construct(private readonly bool $shouldSucceed = true)
    {
    }

    public function push(LocalHisAction $action): bool
    {
        return $this->shouldSucceed;
    }
}
