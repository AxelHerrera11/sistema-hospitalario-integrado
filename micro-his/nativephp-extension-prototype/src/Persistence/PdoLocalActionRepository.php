<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Persistence;

use MicroHis\NativePhpPrototype\Application\LocalActionRepository;
use MicroHis\NativePhpPrototype\Application\LocalActionNotFound;
use MicroHis\NativePhpPrototype\Domain\LocalHisAction;
use MicroHis\NativePhpPrototype\Domain\SyncStatus;
use PDO;

final class PdoLocalActionRepository implements LocalActionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(LocalHisAction $action): void
    {
        $statement = $this->pdo->prepare(
            'insert into nativephp_local_actions (id, tenant_id, actor_id, kind, payload, status, failure_reason)
             values (:id, :tenant_id, :actor_id, :kind, :payload, :status, :failure_reason)
             on conflict (id) do update set
                status = excluded.status,
                failure_reason = excluded.failure_reason'
        );
        $statement->execute([
            ':id' => $action->id,
            ':tenant_id' => $action->tenantId,
            ':actor_id' => $action->actorId,
            ':kind' => $action->kind,
            ':payload' => $action->payload,
            ':status' => $action->status->value,
            ':failure_reason' => $action->failureReason,
        ]);
    }

    public function pendingForTenant(string $tenantId): array
    {
        $statement = $this->pdo->prepare(
            'select id, tenant_id, actor_id, kind, payload, status, failure_reason
             from nativephp_local_actions
             where tenant_id = :tenant_id and status = :status'
        );
        $statement->execute([
            ':tenant_id' => $tenantId,
            ':status' => SyncStatus::Pending->value,
        ]);

        return array_map(
            fn (array $row): LocalHisAction => new LocalHisAction(
                id: (string) $row['id'],
                tenantId: (string) $row['tenant_id'],
                actorId: (string) $row['actor_id'],
                kind: (string) $row['kind'],
                payload: (string) $row['payload'],
                status: SyncStatus::from((string) $row['status']),
                failureReason: $row['failure_reason'] !== null ? (string) $row['failure_reason'] : null,
            ),
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function update(LocalHisAction $action): void
    {
        $statement = $this->pdo->prepare(
            'update nativephp_local_actions
             set status = :status, failure_reason = :failure_reason
             where id = :id and tenant_id = :tenant_id'
        );
        $statement->execute([
            ':id' => $action->id,
            ':tenant_id' => $action->tenantId,
            ':status' => $action->status->value,
            ':failure_reason' => $action->failureReason,
        ]);

        if ($statement->rowCount() === 0) {
            throw new LocalActionNotFound('Accion local no encontrada.');
        }
    }
}
