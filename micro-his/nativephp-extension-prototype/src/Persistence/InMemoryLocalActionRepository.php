<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Persistence;

use MicroHis\NativePhpPrototype\Application\LocalActionRepository;
use MicroHis\NativePhpPrototype\Domain\LocalHisAction;
use MicroHis\NativePhpPrototype\Domain\SyncStatus;

final class InMemoryLocalActionRepository implements LocalActionRepository
{
    /** @var array<string, LocalHisAction> */
    private array $items = [];

    /** @param LocalHisAction[] $items */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->items[$item->id] = $item;
        }
    }

    public function save(LocalHisAction $action): void
    {
        $this->items[$action->id] = $action;
    }

    public function pendingForTenant(string $tenantId): array
    {
        return array_values(array_filter(
            $this->items,
            fn (LocalHisAction $action): bool =>
                $action->tenantId === $tenantId && $action->status === SyncStatus::Pending
        ));
    }
}
