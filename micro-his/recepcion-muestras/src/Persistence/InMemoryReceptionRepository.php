<?php

declare(strict_types=1);

namespace Reception\Persistence;

use Reception\Application\OrderGateway;
use Reception\Application\ReceptionRepository;
use Reception\Domain\CodeCollision;
use Reception\Domain\ReceptionError;

final class InMemoryReceptionRepository implements OrderGateway, ReceptionRepository
{
    public array $samples = [];

    public array $outbox = [];

    public bool $fail = false;

    public function __construct(public array $orders = [], private string $hospital = 'H1') {}

    public function transaction(callable $operation): mixed
    {
        $snapshot = [$this->samples, $this->outbox];
        try {
            return $operation();
        } catch (\Throwable $error) {
            [$this->samples, $this->outbox] = $snapshot;
            throw $error;
        }
    }

    public function order(string $tenant, string $item): ?array
    {
        return $this->orders[$tenant][$item] ?? null;
    }

    public function save(array $sample): void
    {
        foreach ($this->samples as $existing) {
            if ($sample['code'] !== null && $existing['code'] === $sample['code']) {
                throw new CodeCollision('Código duplicado');
            }
            if ($sample['status'] === 'accepted' && $existing['status'] === 'accepted' && $existing['item_id'] === $sample['item_id']) {
                throw new ReceptionError('ORDER_CONFLICT', 'Ítem ya recibido', 409);
            }
        }
        $this->samples[] = $sample;
        $this->outbox[] = ['event_id' => $sample['id'], 'status' => $sample['status']];
        if ($this->fail) {
            throw new \RuntimeException('Persistencia no disponible');
        }
    }

    public function find(string $tenant, string $code): ?array
    {
        foreach ($this->samples as $sample) {
            if ($tenant === $this->hospital && $sample['code'] === $code) {
                return $sample;
            }
        }

        return null;
    }

    public function byRequest(string $tenant, string $key): ?array
    {
        foreach ($this->samples as $sample) {
            if ($tenant === $this->hospital && $sample['request_key'] === $key) {
                return $sample;
            }
        }

        return null;
    }
}
