<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\AuditTrail;

/** Auditoría en memoria para pruebas y demo. Con $down simula la bitácora caída. */
final class InMemoryAuditTrail implements AuditTrail
{
    /** @var list<array{event: string, actor_id: string, detail: string}> */
    public array $events = [];

    public function __construct(private readonly bool $down = false)
    {
    }

    public function record(string $event, string $actorId, string $detail): void
    {
        $this->down && throw new \RuntimeException('auditoria_no_disponible');
        $this->events[] = ['event' => $event, 'actor_id' => $actorId, 'detail' => $detail];
    }
}
