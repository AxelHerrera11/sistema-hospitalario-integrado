<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Integration;

use MicroHis\ExpedienteMedico\Application\Clock;

/** Reloj del servidor en UTC. */
final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
