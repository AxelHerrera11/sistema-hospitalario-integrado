<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\AuditTrail;

/** Auditoría en memoria para pruebas y demo, con las mismas columnas que PdoAuditTrail. Con $down simula la bitácora caída. */
final class InMemoryAuditTrail implements AuditTrail
{
    /** @var list<array{actor_user_ref_id: string, action: string, resource_type: string, resource_id: string, care_episode_id: string, metadata: array<string, scalar>}> */
    public array $events = [];

    public function __construct(private readonly bool $down = false)
    {
    }

    public function record(string $actorId, string $action, string $resourceType, string $resourceId, string $episodeId, array $metadata = []): void
    {
        $this->down && throw new \RuntimeException('auditoria_no_disponible');
        $this->events[] = ['actor_user_ref_id' => $actorId, 'action' => $action, 'resource_type' => $resourceType, 'resource_id' => $resourceId, 'care_episode_id' => $episodeId, 'metadata' => $metadata];
    }
}
