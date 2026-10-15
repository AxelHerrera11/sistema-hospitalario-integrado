<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Persistence;

use MicroHis\IngresoResultados\Application\PendingResultRepository;
use MicroHis\IngresoResultados\Domain\PendingLabResult;

final class InMemoryPendingResultRepository implements PendingResultRepository
{
    /** @var array<string, PendingLabResult> */
    private array $items = [];

    /** @param PendingLabResult[] $items */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->items[$this->key($item->tenantId, $item->id)] = $item;
        }
    }

    public function getById(string $tenantId, string $resultId): PendingLabResult
    {
        return $this->items[$this->key($tenantId, $resultId)]
            ?? throw new \RuntimeException('Resultado pendiente no encontrado.');
    }

    public function save(PendingLabResult $result): void
    {
        $this->items[$this->key($result->tenantId, $result->id)] = $result;
    }

    private function key(string $tenantId, string $resultId): string
    {
        return $tenantId . ':' . $resultId;
    }
}
