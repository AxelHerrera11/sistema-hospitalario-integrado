<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Persistence;

use MicroHis\Rbac\Domain\DenialRecorder;
use MicroHis\Rbac\Domain\Membership;
use MicroHis\Rbac\Domain\PermissionProvider;
use PDO;

/** Persistencia con PDO y sentencias preparadas: ningún valor se concatena en el SQL. */
final class PdoRbacStore implements PermissionProvider, DenialRecorder
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function permissionsFor(string $roleCode): array
    {
        return $this->column('SELECT permission FROM role_permissions WHERE role_code = ?', [$roleCode]);
    }

    public function rolesIn(string $userId, string $hospitalId): array
    {
        return $this->column('SELECT role_code FROM memberships WHERE user_id = ? AND hospital_id = ?', [$userId, $hospitalId]);
    }

    /** Guarda la membresía y su evento de auditoría en una sola transacción. */
    public function assignWithAudit(Membership $m, string $actorId): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare('INSERT INTO memberships (user_id, hospital_id, role_code) VALUES (?, ?, ?)')->execute([$m->userId, $m->hospitalId, $m->roleCode]);
            $this->audit('rol_asignado', $actorId, $m->hospitalId, $m->userId . ':' . $m->roleCode, null);
            $this->pdo->commit();
        } catch (\PDOException $e) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new \RuntimeException('persistencia_no_disponible', 0, $e);
        }
    }

    public function record(array $denial): void
    {
        $this->audit('acceso_denegado', $denial['user_id'], $denial['hospital_id'], $denial['permission'] . ':' . $denial['reason'], $denial['trace_id']);
    }

    private function audit(string $event, string $actorId, string $hospitalId, string $detail, ?string $traceId): void
    {
        $this->pdo->prepare('INSERT INTO audit_log (event, actor_id, hospital_id, detail, trace_id) VALUES (?, ?, ?, ?, ?)')->execute([$event, $actorId, $hospitalId, $detail, $traceId]);
    }

    private function column(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
