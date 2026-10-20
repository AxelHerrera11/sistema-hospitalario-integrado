<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\AuditTrail;
use MicroHis\NotasSoap\Domain\Uuid;
use PDO;

/**
 * Auditoría en local_access_audits. Comparte la conexión del repositorio para entrar en su misma transacción.
 * $correlationId une los eventos de una misma petición (en el API, su trace_id).
 */
final class PdoAuditTrail implements AuditTrail
{
    private readonly string $correlationId;

    public function __construct(private readonly PDO $pdo, ?string $correlationId = null)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->correlationId = $correlationId ?? Uuid::v4();
    }

    public function record(string $actorId, string $action, string $resourceType, string $resourceId, string $episodeId, array $metadata = []): void
    {
        $this->pdo->prepare("INSERT INTO local_access_audits (audit_event_id, correlation_id, actor_user_ref_id, module, action, resource_type, resource_id, care_episode_id, metadata) VALUES (?, ?, ?, 'notas_soap', ?, ?, ?, ?, ?)")
            ->execute([Uuid::v4(), $this->correlationId, $actorId, $action, $resourceType, $resourceId, $episodeId, json_encode((object) $metadata, JSON_THROW_ON_ERROR)]);
    }
}
