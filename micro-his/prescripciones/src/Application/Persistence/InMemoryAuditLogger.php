<?php

declare(strict_types=1);

namespace MicroHis\Prescripciones\Application\Persistence;

use MicroHis\Prescripciones\Application\AuditLogger;
use MicroHis\Prescripciones\Application\Exception\AuditUnavailable;

/** Implementación InMemory para tests. */
final class InMemoryAuditLogger implements AuditLogger
{
    /** @var list<array{event: string, payload: array}> */
    public array $logs = [];

    private bool $fail = false;

    public function record(string $eventName, array $payload): void
    {
        if ($this->fail) {
            throw new AuditUnavailable;
        }
        $this->logs[] = ['event' => $eventName, 'payload' => $payload];
    }

    /** Para tests: simula fallo de auditoría. */
    public function failNext(bool $fail): void
    {
        $this->fail = $fail;
    }

    /** Para tests: limpia logs. */
    public function clear(): void
    {
        $this->logs = [];
    }
}