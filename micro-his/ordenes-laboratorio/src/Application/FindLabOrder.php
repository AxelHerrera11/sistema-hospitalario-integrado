<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

use MicroHis\OrdenesLab\Domain\DomainRuleViolation;
use MicroHis\OrdenesLab\Domain\LabOrder;

/** UC-OL-02 Consultar una orden y su historial, siempre dentro del tenant. */
final class FindLabOrder
{
    public function __construct(private readonly LabOrderRepository $repository)
    {
    }

    public function execute(string $tenant, string $orderId): LabOrder
    {
        return $this->repository->find($tenant, $orderId)
            ?? throw new DomainRuleViolation('ORDER_NOT_FOUND', 'lab_order_id');
    }
}
