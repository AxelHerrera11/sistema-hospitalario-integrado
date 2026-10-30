<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

use Closure;
use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Domain\LabOrder;

/** UC-OL-04 Cancelar una orden conservando actor, fecha y motivo. */
final class CancelLabOrder
{
    /** @param Closure(): string $newEventId @param Closure(): string $now */
    public function __construct(
        private readonly LabOrderRepository $repository,
        private readonly Closure $newEventId,
        private readonly Closure $now,
    ) {
    }

    public function execute(string $tenant, string $orderId, string $actor, string $reason): LabOrder
    {
        $order = $this->repository->find($tenant, $orderId)
            ?? throw new DomainRuleViolation('ORDER_NOT_FOUND', 'lab_order_id');
        $updated = $order->transitionTo('CANCELLED', $actor, ($this->now)(), ($this->newEventId)(), $reason);
        $events = $updated->events;
        $this->repository->updateStatus($tenant, $updated, $events[count($events) - 1]);

        return $updated;
    }
}
