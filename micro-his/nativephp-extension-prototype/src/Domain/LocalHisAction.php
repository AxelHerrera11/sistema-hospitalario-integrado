<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Domain;

final class LocalHisAction
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $actorId,
        public readonly string $kind,
        public readonly string $payload,
        public SyncStatus $status = SyncStatus::Pending,
        public ?string $failureReason = null,
    ) {
        if (trim($kind) === '') {
            throw new DomainRuleViolation('El tipo de accion local es obligatorio.');
        }

        if (trim($payload) === '') {
            throw new DomainRuleViolation('El payload local no puede estar vacio.');
        }
    }

    public function markSynced(): void
    {
        $this->status = SyncStatus::Synced;
        $this->failureReason = null;
    }

    public function markFailed(string $reason): void
    {
        $this->status = SyncStatus::Failed;
        $this->failureReason = $reason;
    }
}
