<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Integration;

use MicroHis\Gobernanza\Application\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
