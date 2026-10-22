<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Persistence;

use MicroHis\IngresoResultados\Application\AuditLogger;

final class ArrayAuditLogger implements AuditLogger
{
    /** @var array<int, array<string, string|int|null>> */
    public array $events = [];

    /** @param array<string, string|int|null> $event */
    public function record(array $event): void
    {
        $this->events[] = $event;
    }
}
