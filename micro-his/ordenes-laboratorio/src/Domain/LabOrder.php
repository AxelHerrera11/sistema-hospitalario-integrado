<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Domain;

/**
 * Orden de laboratorio emitida desde el expediente.
 *
 * Invariantes: nace ISSUED con al menos un ítem y sin pruebas repetidas; solo cambia por las
 * transiciones del catálogo; cada cambio produce un evento de trazabilidad inmutable.
 */
final class LabOrder
{
    public const STATUSES = ['ISSUED', 'COLLECTED', 'IN_PROCESS', 'RESULTED', 'VALIDATED', 'CANCELLED'];

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'ISSUED' => ['COLLECTED', 'CANCELLED'],
        'COLLECTED' => ['IN_PROCESS', 'CANCELLED'],
        'IN_PROCESS' => ['RESULTED', 'CANCELLED'],
        'RESULTED' => ['VALIDATED'],
        'VALIDATED' => [],
        'CANCELLED' => [],
    ];

    /** @param list<LabOrderItem> $items @param list<LabOrderEvent> $events */
    private function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $patientId,
        public readonly string $careEpisodeId,
        public readonly ?string $clinicalNoteId,
        public readonly string $orderedBy,
        public readonly string $priority,
        public readonly ?string $clinicalContext,
        public readonly string $idempotencyKey,
        public readonly string $correlationId,
        public readonly string $status,
        public readonly array $items,
        public readonly array $events,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    /**
     * Emite una orden nueva. El contexto del expediente y la vigencia del catálogo ya fueron
     * comprobados por la capa de aplicación; aquí se validan las invariantes propias de la orden.
     *
     * @param array<string, mixed> $draft
     * @param list<LabOrderItem> $items
     */
    public static function issue(
        array $draft,
        array $items,
        string $actor,
        string $now,
        string $correlationId,
        string $eventId,
    ): self {
        foreach (['id', 'tenant_id', 'local_patient_id', 'care_episode_id', 'idempotency_key'] as $field) {
            if (!is_string($draft[$field] ?? null) || trim($draft[$field]) === '') {
                throw new DomainRuleViolation('FIELD_REQUIRED', $field);
            }
        }
        if (trim($actor) === '') {
            throw new DomainRuleViolation('ACTOR_REQUIRED', 'actor');
        }
        $priority = strtoupper(trim((string) ($draft['priority'] ?? 'ROUTINE')));
        if (!in_array($priority, ['ROUTINE', 'URGENT', 'STAT'], true)) {
            throw new DomainRuleViolation('PRIORITY_UNSUPPORTED', 'priority');
        }
        self::assertUniqueItems($items);

        $order = new self(
            trim($draft['id']),
            trim($draft['tenant_id']),
            trim($draft['local_patient_id']),
            trim($draft['care_episode_id']),
            self::optionalString($draft['clinical_note_id'] ?? null),
            trim($actor),
            $priority,
            self::optionalString($draft['clinical_context'] ?? null),
            trim($draft['idempotency_key']),
            $correlationId,
            'ISSUED',
            $items,
            [new LabOrderEvent($eventId, null, 'ISSUED', $actor, $now, $correlationId)],
            $now,
            $now,
        );

        return $order;
    }

    /**
     * Reconstruye una orden ya persistida (hidratación). No vuelve a validar reglas: la base y la
     * aplicación ya las aceptaron; solo reproduce el estado guardado.
     *
     * @param list<LabOrderItem> $items
     * @param list<LabOrderEvent> $events
     */
    public static function restore(
        string $id,
        string $tenantId,
        string $patientId,
        string $careEpisodeId,
        ?string $clinicalNoteId,
        string $orderedBy,
        string $priority,
        ?string $clinicalContext,
        string $idempotencyKey,
        string $correlationId,
        string $status,
        array $items,
        array $events,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self($id, $tenantId, $patientId, $careEpisodeId, $clinicalNoteId, $orderedBy, $priority,
            $clinicalContext, $idempotencyKey, $correlationId, $status, $items, $events, $createdAt, $updatedAt);
    }

    /** Avanza la orden a un estado permitido y deja el evento correspondiente. */
    public function transitionTo(
        string $nextStatus,
        string $actor,
        string $now,
        string $eventId,
        ?string $reason = null,
    ): self {
        $nextStatus = strtoupper(trim($nextStatus));
        if (trim($actor) === '') {
            throw new DomainRuleViolation('ACTOR_REQUIRED', 'actor');
        }
        if ($this->status === 'VALIDATED' && $nextStatus === 'CANCELLED') {
            throw new DomainRuleViolation('ORDER_ALREADY_VALIDATED', 'status');
        }
        if (!in_array($nextStatus, self::STATUSES, true) || !in_array($nextStatus, self::TRANSITIONS[$this->status], true)) {
            throw new DomainRuleViolation('INVALID_STATUS_TRANSITION', 'status');
        }
        $reason = self::optionalString($reason);
        if ($nextStatus === 'CANCELLED') {
            if ($reason === null) {
                throw new DomainRuleViolation('CANCELLATION_REASON_REQUIRED', 'reason');
            }
            $length = function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') : strlen($reason);
            if ($length < 5 || $length > 250) {
                throw new DomainRuleViolation('CANCELLATION_REASON_INVALID', 'reason');
            }
        }
        $events = $this->events;
        $events[] = new LabOrderEvent($eventId, $this->status, $nextStatus, $actor, $now, $this->correlationId, $reason);

        return new self(
            $this->id,
            $this->tenantId,
            $this->patientId,
            $this->careEpisodeId,
            $this->clinicalNoteId,
            $this->orderedBy,
            $this->priority,
            $this->clinicalContext,
            $this->idempotencyKey,
            $this->correlationId,
            $nextStatus,
            $this->items,
            $events,
            $this->createdAt,
            $now,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lab_order_id' => $this->id,
            'local_patient_id' => $this->patientId,
            'care_episode_id' => $this->careEpisodeId,
            'clinical_note_id' => $this->clinicalNoteId,
            'ordered_by' => $this->orderedBy,
            'priority' => $this->priority,
            'clinical_context' => $this->clinicalContext,
            'status' => $this->status,
            'correlation_id' => $this->correlationId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'items' => array_map(static fn (LabOrderItem $item): array => $item->toArray(), $this->items),
            'events' => array_map(static fn (LabOrderEvent $event): array => $event->toArray(), $this->events),
        ];
    }

    /** @param list<LabOrderItem> $items */
    private static function assertUniqueItems(array $items): void
    {
        if ($items === []) {
            throw new DomainRuleViolation('ORDER_ITEMS_INVALID', 'items');
        }
        $ids = array_map(static fn (LabOrderItem $item): string => $item->labTestId, $items);
        if (count($ids) !== count(array_unique($ids))) {
            throw new DomainRuleViolation('ORDER_ITEMS_INVALID', 'items');
        }
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
