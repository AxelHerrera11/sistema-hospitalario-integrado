<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Persistence;

use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditPage;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;

/**
 * Adaptador en memoria (desarrollo y pruebas). Append-only: no ofrece
 * modificación ni borrado. Mismo orden que el adaptador PDO:
 * occurred_at DESC, event_id DESC.
 */
final class InMemoryAuditLog implements AuditLogger, AuditEventReader
{
    /** @var array<string, AuditEvent> */
    private array $events = [];

    public function append(AuditEvent $event): void
    {
        if (isset($this->events[$event->eventId])) {
            throw new AuditStorageFailure('audit_duplicate_event');
        }
        $this->events[$event->eventId] = $event;
    }

    public function search(string $tenantId, AuditFilter $filter): AuditPage
    {
        $matches = array_values(array_filter(
            $this->events,
            static fn (AuditEvent $e): bool => $e->tenantId === $tenantId && $filter->matches($e),
        ));
        usort($matches, static fn (AuditEvent $a, AuditEvent $b): int => [$b->occurredAt, $b->eventId] <=> [$a->occurredAt, $a->eventId]);

        return new AuditPage(
            array_slice($matches, $filter->offset(), $filter->perPage),
            count($matches),
            $filter->page,
            $filter->perPage,
        );
    }

    /** Solo para pruebas y demostración: número total de eventos guardados. */
    public function count(): int
    {
        return count($this->events);
    }
}
