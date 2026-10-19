<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Infrastructure\Persistence\PDO;

use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use PDO;

final class PDOSessionRepository implements SessionRepositoryInterface
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function save(Session $session): void
    {
        $sql = "
            INSERT INTO user_sessions (session_id, user_id, tenant_id, device_id, device_ip, device_user_agent, device_trust_score, device_is_known, created_at, last_activity_at, revoked)
            VALUES (:session_id, :user_id, :tenant_id, :device_id, :device_ip, :device_ua, :trust_score, :is_known, :created_at, :last_activity, :revoked)
            ON CONFLICT (session_id) DO UPDATE SET
                last_activity_at = EXCLUDED.last_activity_at,
                revoked = EXCLUDED.revoked
        ";

        $device = $session->device();
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'session_id' => $session->id()->value(),
            'user_id' => $session->userId()->value(),
            'tenant_id' => $session->tenantId()->value(),
            'device_id' => $device->deviceId(),
            'device_ip' => $device->ip(),
            'device_ua' => $device->userAgent(),
            'trust_score' => $device->trustScore(),
            'is_known' => $device->isKnown(),
            'created_at' => $session->createdAt()->format('Y-m-d H:i:s.u'),
            'last_activity' => $session->lastActivityAt()->format('Y-m-d H:i:s.u'),
            'revoked' => $session->isRevoked(),
        ]);
    }

    public function findById(SessionId $id): ?Session
    {
        $sql = "SELECT * FROM user_sessions WHERE session_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['id' => $id->value()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToSession($row);
    }

    public function findByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM user_sessions WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['user_id' => $userId->value()]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->mapRowToSession($row), $rows);
    }

    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?Session
    {
        $sql = "SELECT * FROM user_sessions WHERE user_id = :user_id AND device_id = :device_id AND revoked = false ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'user_id' => $userId->value(),
            'device_id' => $deviceId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToSession($row);
    }

    public function findAllByUserId(UserId $userId): array
    {
        return $this->findByUserId($userId);
    }

    public function delete(SessionId $id): void
    {
        $sql = "UPDATE user_sessions SET revoked = true WHERE session_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['id' => $id->value()]);
    }

    public function deleteAllByUserId(UserId $userId): void
    {
        $sql = "UPDATE user_sessions SET revoked = true WHERE user_id = :user_id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['user_id' => $userId->value()]);
    }

    public function findIdleSessions(int $idleTimeoutSeconds): array
    {
        $threshold = (new \DateTimeImmutable())->modify("-{$idleTimeoutSeconds} seconds")->format('Y-m-d H:i:s.u');
        $sql = "SELECT * FROM user_sessions WHERE last_activity_at < :threshold AND revoked = false";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['threshold' => $threshold]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->mapRowToSession($row), $rows);
    }

    private function mapRowToSession(array $row): Session
    {
        $device = new DeviceFingerprint(
            $row['device_id'],
            $row['device_ip'],
            $row['device_user_agent'],
            (int)$row['device_trust_score'],
            (bool)$row['device_is_known']
        );

        return Session::reconstitute(
            SessionId::fromString($row['session_id']),
            UserId::fromString($row['user_id']),
            TenantId::fromString($row['tenant_id']),
            $device,
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['last_activity_at']),
            (bool)$row['revoked']
        );
    }
}