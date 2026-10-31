<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Application;

use MicroHis\OrdenesLab\Domain\LabOrder;
use MicroHis\OrdenesLab\Domain\LabOrderEvent;

/** Puerto de persistencia de órdenes. La infraestructura lo implementa; el dominio no lo conoce. */
interface LabOrderRepository
{
    public function find(string $tenant, string $id): ?LabOrder;

    public function findByIdempotency(string $tenant, string $key): ?LabOrder;

    public function create(string $tenant, LabOrder $order): void;

    /** Actualiza el estado de la orden e inserta exactamente el evento nuevo (historial append-only). */
    public function updateStatus(string $tenant, LabOrder $order, LabOrderEvent $event): void;

    /**
     * @param array{patient_id?: string, episode_id?: string, status?: string} $filters
     * @return list<LabOrder>
     */
    public function search(string $tenant, array $filters, int $limit, int $offset): array;
}
