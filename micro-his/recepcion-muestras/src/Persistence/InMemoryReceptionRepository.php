<?php
declare(strict_types=1);
namespace Reception\Persistence;

use Reception\Application\ReceptionRepository;

final class InMemoryReceptionRepository implements ReceptionRepository
{
    public array $samples = [];
    public array $outbox = [];
    public bool $fail = false;
    public function __construct(public array $orders = []) {}

    public function transaction(callable $operation): mixed
    {
        $snapshot = [$this->orders, $this->samples, $this->outbox];
        try { return $operation(); }
        catch (\Throwable $e) {
            [$this->orders, $this->samples, $this->outbox] = $snapshot;
            throw $e;
        }
    }

    public function order(string $tenant, string $item): ?array
    {
        return $this->orders[$tenant][$item] ?? null;
    }

    public function save(array $sample): void
    {
        if ($this->fail) throw new \RuntimeException('Persistencia no disponible');
        $this->samples[] = $sample;
        $this->orders[$sample['tenant_id']][$sample['item_id']]['status'] =
            $sample['status'] === 'accepted' ? 'received' : 'pending_new_sample';
        if ($sample['status'] === 'rejected') $this->outbox[] = $sample;
    }

    public function find(string $tenant, string $code): ?array
    {
        foreach ($this->samples as $sample) {
            if ($sample['tenant_id'] === $tenant && $sample['code'] === $code) return $sample;
        }
        return null;
    }
}
