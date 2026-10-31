<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Integration;

use MicroHis\QaCalidad\Application\Clock;

final class SystemClock implements Clock
{
    public function now(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
    }
}
