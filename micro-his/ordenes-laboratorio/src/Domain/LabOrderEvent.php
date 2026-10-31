<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Domain;

/** Evento de trazabilidad de una orden. Es de solo inserción: representa una transición ya ocurrida. */
final readonly class LabOrderEvent
{
    public function __construct(
        public string $id,
        public ?string $fromStatus,
        public string $toStatus,
        public string $actor,
        public string $occurredAt,
        public string $correlationId,
        public ?string $reason = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'event_id' => $this->id,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'actor' => $this->actor,
            'occurred_at' => $this->occurredAt,
            'correlation_id' => $this->correlationId,
            'reason' => $this->reason,
        ];
    }
}
