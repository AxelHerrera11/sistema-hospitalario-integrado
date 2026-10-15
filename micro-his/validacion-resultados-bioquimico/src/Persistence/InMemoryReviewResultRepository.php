<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Persistence;

use MicroHis\ValidacionResultados\Application\ReviewResultRepository;
use MicroHis\ValidacionResultados\Domain\ReviewableLabResult;

final class InMemoryReviewResultRepository implements ReviewResultRepository
{
    /** @var array<string, ReviewableLabResult> */
    private array $items = [];

    /** @param ReviewableLabResult[] $items */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->items[$this->key($item->tenantId, $item->id)] = $item;
        }
    }

    public function getPending(string $tenantId, string $resultId): ReviewableLabResult
    {
        return $this->items[$this->key($tenantId, $resultId)]
            ?? throw new \RuntimeException('Resultado en revision no encontrado.');
    }

    public function save(ReviewableLabResult $result): void
    {
        $this->items[$this->key($result->tenantId, $result->id)] = $result;
    }

    private function key(string $tenantId, string $resultId): string
    {
        return $tenantId . ':' . $resultId;
    }
}
