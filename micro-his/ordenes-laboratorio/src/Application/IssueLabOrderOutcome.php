<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

use MicroHis\OrdenesLab\Domain\LabOrder;

/** Resultado de emitir: distingue una orden nueva de una recuperada por idempotencia (200 vs 201). */
final class IssueLabOrderOutcome
{
    public function __construct(
        public readonly LabOrder $order,
        public readonly bool $replayed,
    ) {
    }
}
