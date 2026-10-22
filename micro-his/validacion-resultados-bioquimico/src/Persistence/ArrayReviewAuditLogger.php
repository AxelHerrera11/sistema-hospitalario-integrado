<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Persistence;

use MicroHis\ValidacionResultados\Application\ReviewAuditLogger;

final class ArrayReviewAuditLogger implements ReviewAuditLogger
{
    /** @var array<int, array<string, string|int|null>> */
    public array $events = [];

    /** @param array<string, string|int|null> $event */
    public function record(array $event): void
    {
        $this->events[] = $event;
    }
}
