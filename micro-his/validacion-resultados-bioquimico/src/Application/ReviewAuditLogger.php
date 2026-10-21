<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Application;

interface ReviewAuditLogger
{
    /** @param array<string, string|int|null> $event */
    public function record(array $event): void;
}
