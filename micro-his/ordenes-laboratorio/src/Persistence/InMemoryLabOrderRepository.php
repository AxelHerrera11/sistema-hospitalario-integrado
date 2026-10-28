<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Persistence;

use MicroHis\OrdenesLab\Application\LabOrderRepository;
use MicroHis\OrdenesLab\Application\PersistenceFailure;
use MicroHis\OrdenesLab\Domain\LabOrder;
use MicroHis\OrdenesLab\Domain\LabOrderEvent;

/** Adaptador en memoria: misma interfaz que PDO, sin base de datos. Base de las pruebas de contrato. */
final class InMemoryLabOrderRepository implements LabOrderRepository
{
    /** @var array<string, LabOrder> */
    private array $orders = [];

    /** @var array<string, string> clave "tenant|key" → id de orden */
    private array $keys = [];

    public bool $failNextCreate = false;

    public int $eventsStored = 0;

    public function find(string $tenant, string $id): ?LabOrder
    {
        return $this->orders["{$tenant}|{$id}"] ?? null;
    }

    public function findByIdempotency(string $tenant, string $key): ?LabOrder
    {
        $id = $this->keys["{$tenant}|{$key}"] ?? null;

        return $id === null ? null : ($this->orders["{$tenant}|{$id}"] ?? null);
    }

    public function create(string $tenant, LabOrder $order): void
    {
        if ($this->failNextCreate) {
            $this->failNextCreate = false;
            throw new PersistenceFailure();
        }
        $key = "{$tenant}|{$order->idempotencyKey}";
        if (isset($this->keys[$key])) {
            throw new PersistenceFailure(true);
        }
        $this->orders["{$tenant}|{$order->id}"] = $order;
        $this->keys[$key] = $order->id;
        $this->eventsStored += count($order->events);
    }

    public function updateStatus(string $tenant, LabOrder $order, LabOrderEvent $event): void
    {
        $this->orders["{$tenant}|{$order->id}"] = $order;
        $this->eventsStored++;
    }

    public function search(string $tenant, array $filters, int $limit, int $offset): array
    {
        $orders = [];
        foreach ($this->orders as $key => $order) {
            if (!str_starts_with($key, "{$tenant}|")) {
                continue;
            }
            if (($filters['patient_id'] ?? '') !== '' && $order->patientId !== $filters['patient_id']) {
                continue;
            }
            if (($filters['episode_id'] ?? '') !== '' && $order->careEpisodeId !== $filters['episode_id']) {
                continue;
            }
            if (($filters['status'] ?? '') !== '' && $order->status !== $filters['status']) {
                continue;
            }
            $orders[] = $order;
        }
        usort($orders, static fn (LabOrder $a, LabOrder $b): int => strcmp($b->createdAt, $a->createdAt));

        return array_slice($orders, $offset, $limit);
    }
}
