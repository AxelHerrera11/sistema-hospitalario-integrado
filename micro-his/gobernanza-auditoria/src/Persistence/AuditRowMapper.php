<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Persistence;

use MicroHis\Gobernanza\Domain\AuditEvent;

/**
 * Traduce entre AuditEvent y las columnas de audit_log:
 * event_id, occurred_at, event, actor_id, hospital_id, detail (JSON),
 * trace_id, result, previous_hash, event_hash.
 *
 * detail guarda resource, context, criticality, reason y redacted_keys, más
 * las claves user_id / hospital_id / tenant_id / trace_id que ya escribe el
 * repositorio de Tenants, para que ambos productores sean legibles.
 */
final class AuditRowMapper
{
    /** Claves de detail que no forman parte del contexto visible. */
    private const RESERVED = ['context', 'criticality', 'reason', 'redacted_keys', 'resource', 'user_id', 'hospital_id', 'tenant_id', 'trace_id', 'actor_id', 'result', 'severity'];

    /** @return array<string, mixed> */
    public function detail(AuditEvent $event): array
    {
        return [
            'context' => $event->context,
            'criticality' => $event->criticality?->value,
            'hospital_id' => $event->tenantId,
            'reason' => $event->reason,
            'redacted_keys' => $event->redactedKeys,
            'resource' => $event->resourceType === null ? null : ['id' => $event->resourceId, 'type' => $event->resourceType],
            'tenant_id' => $event->tenantId,
            'trace_id' => $event->correlationId,
            'user_id' => $event->actorId,
        ];
    }

    /**
     * @param array<string, mixed> $row fila leída de audit_log
     * @throws \JsonException|\Exception si la fila está corrupta
     */
    public function toEvent(array $row): AuditEvent
    {
        $detail = json_decode((string) $row['detail'], true, 32, JSON_THROW_ON_ERROR);
        $detail = is_array($detail) ? $detail : [];
        $resource = is_array($detail['resource'] ?? null) ? $detail['resource'] : [];
        $context = is_array($detail['context'] ?? null)
            ? $detail['context']
            : array_diff_key($detail, array_flip(self::RESERVED));

        return AuditEvent::rehydrate(
            (string) $row['event_id'],
            new \DateTimeImmutable((string) $row['occurred_at']),
            (string) $row['actor_id'],
            (string) $row['hospital_id'],
            (string) $row['event'],
            isset($row['result']) ? (string) $row['result'] : null,
            is_string($detail['criticality'] ?? null) ? $detail['criticality'] : null,
            is_string($resource['type'] ?? null) ? $resource['type'] : null,
            is_scalar($resource['id'] ?? null) ? (string) $resource['id'] : null,
            isset($row['trace_id']) ? (string) $row['trace_id'] : null,
            is_string($detail['reason'] ?? null) ? $detail['reason'] : null,
            $context,
            is_array($detail['redacted_keys'] ?? null) ? $detail['redacted_keys'] : [],
        );
    }
}
