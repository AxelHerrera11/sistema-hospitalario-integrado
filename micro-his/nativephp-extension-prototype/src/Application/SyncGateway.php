<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Application;

use MicroHis\NativePhpPrototype\Domain\LocalHisAction;

interface SyncGateway
{
    public function push(LocalHisAction $action): bool;
}
