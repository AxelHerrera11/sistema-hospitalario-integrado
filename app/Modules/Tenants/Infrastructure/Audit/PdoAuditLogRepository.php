<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Audit;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use PDO;
use PDOException;
final class PdoAuditLogRepository implements AuditLogRepositoryInterface
{
    private PDO $connection;
    private string $previousHash = '';
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->initializeHashChain();
    }
    private function initializeHashChain(): void
    {
        $stmt = $this->connection->query("SELECT hash_chain FROM audit_log ORDER BY created_at DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->previousHash = $row['hash_chain'] ?? '';
    }
    public function log(DomainEvent $event): void
    {
        $payload = $event->payload();
        $payloadJson = json_encode($payload);
        $currentHash = $this->calculateHash($payloadJson);
        $sql = "
            INSERT INTO audit_log (event_id, event_name, payload, severity, previous_hash, hash_chain, occurred_at)
            VALUES (:event_id, :event_name, :payload, :severity, :prev_hash, :curr_hash, :occurred_at)
        ";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                'event_id' => $event->eventId(),
                'event_name' => $event->eventName(),
                'payload' => $payloadJson,
                'severity' => $payload['severity'] ?? 'INFO',
                'prev_hash' => $this->previousHash,
                'curr_hash' => $currentHash,
                'occurred_at' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
            ]);
            $this->previousHash = $currentHash;
        } catch (PDOException $e) {
            throw new \RuntimeException('Error registrando auditoría: ' . $e->getMessage());
        }
    }
    public function logRaw(string $eventName, array $payload, ?string $severity = null): void
    {
        $payloadJson = json_encode($payload);
        $currentHash = $this->calculateHash($payloadJson);
        $sql = "
            INSERT INTO audit_log (event_id, event_name, payload, severity, previous_hash, hash_chain, occurred_at)
            VALUES (:event_id, :event_name, :payload, :severity, :prev_hash, :curr_hash, :occurred_at)
        ";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                'event_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'event_name' => $eventName,
                'payload' => $payloadJson,
                'severity' => $severity ?? 'INFO',
                'prev_hash' => $this->previousHash,
                'curr_hash' => $currentHash,
                'occurred_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.u'),
            ]);

            $this->previousHash = $currentHash;
        } catch (PDOException $e) {
            throw new \RuntimeException('Error registrando auditoría raw: ' . $e->getMessage());
        }
    }
    public function findByUserId(string $userId, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT * FROM audit_log
            WHERE payload->>'user_id' = :user_id
            ORDER BY occurred_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findByTenantId(string $tenantId, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT * FROM audit_log
            WHERE payload->>'tenant_id' = :tenant_id
            ORDER BY occurred_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findByEventName(string $eventName, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT * FROM audit_log
            WHERE event_name = :event_name
            ORDER BY occurred_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':event_name', $eventName);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT * FROM audit_log
            WHERE occurred_at BETWEEN :from AND :to
            ORDER BY occurred_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':from', $from->format('Y-m-d H:i:s.u'));
        $stmt->bindValue(':to', $to->format('Y-m-d H:i:s.u'));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private function calculateHash(string $payload): string
    {
        $data = $this->previousHash . $payload . (new \DateTimeImmutable())->format('Y-m-d H:i:s.u');
        return hash('sha256', $data);
    }
}
