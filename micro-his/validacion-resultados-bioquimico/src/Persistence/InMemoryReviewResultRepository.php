<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Persistence;

use MicroHis\ValidacionResultados\Application\ReviewResultRepository;
use MicroHis\ValidacionResultados\Application\ReviewAuditLogger;
use MicroHis\ValidacionResultados\Application\ReviewResultNotFound;
use MicroHis\ValidacionResultados\Domain\ReviewableLabResult;

final class InMemoryReviewResultRepository implements ReviewResultRepository
{
    /** @var array<string, ReviewableLabResult> */
    private array $items = [];

    /** @param ReviewableLabResult[] $items */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->items[$this->key($item->tenantId, $item->id)] = clone $item;
        }
    }

    public function getPending(string $tenantId, string $resultId): ReviewableLabResult
    {
        $result = $this->items[$this->key($tenantId, $resultId)]
            ?? throw new ReviewResultNotFound('Resultado en revision no encontrado.');

        return clone $result;
    }

    public function save(ReviewableLabResult $result): void
    {
        $this->items[$this->key($result->tenantId, $result->id)] = clone $result;
    }

    public function saveWithAudit(ReviewableLabResult $result, array $event, ReviewAuditLogger $audit): void
    {
        $key = $this->key($result->tenantId, $result->id);
        $previous = $this->items[$key] ?? throw new ReviewResultNotFound('Resultado en revision no encontrado.');

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
