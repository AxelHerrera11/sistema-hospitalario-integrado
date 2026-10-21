<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Persistence;

use MicroHis\IngresoResultados\Application\PendingResultRepository;
use MicroHis\IngresoResultados\Application\AuditLogger;
use MicroHis\IngresoResultados\Application\PendingResultNotFound;
use MicroHis\IngresoResultados\Domain\PendingLabResult;

final class InMemoryPendingResultRepository implements PendingResultRepository
{
    /** @var array<string, PendingLabResult> */
    private array $items = [];

    /** @param PendingLabResult[] $items */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->items[$this->key($item->tenantId, $item->id)] = clone $item;
        }
    }

    public function getById(string $tenantId, string $resultId): PendingLabResult
    {
        $result = $this->items[$this->key($tenantId, $resultId)]
            ?? throw new PendingResultNotFound('Resultado pendiente no encontrado.');

        return clone $result;
    }

    public function save(PendingLabResult $result): void
    {
        $this->items[$this->key($result->tenantId, $result->id)] = clone $result;
    }

    public function saveWithAudit(PendingLabResult $result, array $event, AuditLogger $audit): void
    {
        $key = $this->key($result->tenantId, $result->id);
        $previous = $this->items[$key] ?? throw new PendingResultNotFound('Resultado pendiente no encontrado.');

        try {
            $this->items[$key] = clone $result;
            $audit->record($event);
        } catch (\Throwable $error) {
            $this->items[$key] = $previous;
            throw $error;
        }
    }

    private function key(string $tenantId, string $resultId): string
    {
        return $tenantId . ':' . $resultId;
    }
}
