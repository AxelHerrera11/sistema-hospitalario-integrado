<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Presentation;

use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditPage;

/**
 * Representación pública de un evento. No expone hashes de la cadena ni
 * detalles internos de almacenamiento; el contexto ya viene filtrado por
 * ContextSanitizer (también al leer filas de otros productores).
 */
final class AuditEventPresenter
{
    /** @return array<string, mixed> */
    public function event(AuditEvent $event): array
    {
        return [
            'event_id' => $event->eventId,
            'occurred_at' => $event->occurredAt->format('Y-m-d\TH:i:s.v\Z'),
            'actor_id' => $event->actorId,
            'tenant_id' => $event->tenantId,
            'module' => $event->module,
            'action' => $event->action,
            'event' => $event->eventName(),
            'resource' => $event->resourceType === null ? null : ['type' => $event->resourceType, 'id' => $event->resourceId],
            'outcome' => $event->outcome->value,
            'occurred' => $event->outcome->occurred(),
            'criticality' => $event->criticality?->value,
            'correlation_id' => $event->correlationId,
            'reason' => $event->reason,
            'context' => $event->context === [] ? new \stdClass() : $event->context,
            'redacted_keys' => $event->redactedKeys,
        ];
    }

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function page(AuditPage $page): array
    {
        return [
            'data' => array_map([$this, 'event'], $page->events),
            'meta' => [
                'page' => $page->page,
                'per_page' => $page->perPage,
                'total' => $page->total,
                'total_pages' => $page->totalPages(),
            ],
        ];
    }
}
