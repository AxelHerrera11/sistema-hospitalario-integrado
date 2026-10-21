<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Application;

interface AuditLogger
{
    /** @param array<string, string|int|null> $event */
    public function record(array $event): void;
}
