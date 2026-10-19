<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use DateTimeImmutable;
final class InMemoryAuditLogRepository implements AuditLogRepositoryInterface
{
    private array $logs = [];
    private string $previousHash = '';
    public function log(DomainEvent $event): void
    {
        $payload = $event->payload();
        $payloadJson = json_encode($payload);
        $currentHash = $this->calculateHash($payloadJson);
        $this->logs[] = [
            'event_id' => $event->eventId(),
            'event_name' => $event->eventName(),
            'payload' => $payload,
            'severity' => $payload['severity'] ?? 'INFO',
            'previous_hash' => $this->previousHash,
            'hash_chain' => $currentHash,
            'occurred_at' => $event->occurredAt(),];
        $this->previousHash = $currentHash;}
    public function logRaw(string $eventName, array $payload, ?string $severity = null): void
    {
        $payloadJson = json_encode($payload);
        $currentHash = $this->calculateHash($payloadJson);
        $this->logs[] = [
            'event_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'event_name' => $eventName,
            'payload' => $payload,
            'severity' => $severity ?? 'INFO',
            'previous_hash' => $this->previousHash,
            'hash_chain' => $currentHash,
            'occurred_at' => new DateTimeImmutable(),];
        $this->previousHash = $currentHash;}
    public function findByUserId(string $userId, int $limit = 100, int $offset = 0): array
    {
        $filtered = array_filter(
            $this->logs,
            fn(array $log) => ($log['payload']['user_id'] ?? null) === $userId);
        return array_slice(array_values($filtered), $offset, $limit);}
    public function findByTenantId(string $tenantId, int $limit = 100, int $offset = 0): array
    {
        $filtered = array_filter(
            $this->logs,
            fn(array $log) => ($log['payload']['tenant_id'] ?? null) === $tenantId);
        return array_slice(array_values($filtered), $offset, $limit);}
    public function findByEventName(string $eventName, int $limit = 100, int $offset = 0): array
    {
        $filtered = array_filter(
            $this->logs,
            fn(array $log) => $log['event_name'] === $eventName);
        return array_slice(array_values($filtered), $offset, $limit);}
    public function findByDateRange(DateTimeImmutable $from, DateTimeImmutable $to, int $limit = 100, int $offset = 0): array
    {
        $filtered = array_filter(
            $this->logs,
            fn(array $log) => $log['occurred_at'] >= $from && $log['occurred_at'] <= $to);
        return array_slice(array_values($filtered), $offset, $limit);}
    public function verifyHashChain(): bool
    {
        $prevHash = '';
        foreach ($this->logs as $log) {
            if ($log['previous_hash'] !== $prevHash) {
                return false;}
            $prevHash = $log['hash_chain'];}
        return true;}
    public function getLogs(): array
    {
        return $this->logs;}
    public function clear(): void
    {
        $this->logs = [];
        $this->previousHash = '';}
    private function calculateHash(string $payload): string
    {
        $data = $this->previousHash . $payload . (new DateTimeImmutable())->format('Y-m-d H:i:s.u');
        return hash('sha256', $data);
    }
}
