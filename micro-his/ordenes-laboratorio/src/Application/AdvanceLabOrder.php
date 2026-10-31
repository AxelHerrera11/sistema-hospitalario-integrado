<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

use Closure;
use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Domain\LabOrder;

/** UC-OL-03 Avanzar el estado de la orden por una transición permitida. */
final class AdvanceLabOrder
{
    /** @param Closure(): string $newEventId @param Closure(): string $now */
    public function __construct(
        private readonly LabOrderRepository $repository,
        private readonly Closure $newEventId,
        private readonly Closure $now,
    ) {
    }

    public function execute(string $tenant, string $orderId, string $nextStatus, string $actor): LabOrder
    {
        $order = $this->repository->find($tenant, $orderId)
            ?? throw new DomainRuleViolation('ORDER_NOT_FOUND', 'lab_order_id');
        $updated = $order->transitionTo($nextStatus, $actor, ($this->now)(), ($this->newEventId)());
        $events = $updated->events;
        $this->repository->updateStatus($tenant, $updated, $events[count($events) - 1]);

        return $updated;
    }
}
