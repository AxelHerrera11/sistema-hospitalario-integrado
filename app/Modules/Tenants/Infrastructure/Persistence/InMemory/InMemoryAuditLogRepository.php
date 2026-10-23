<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
/** Bitácora de solo inserción con cadena de hashes verificable (cada entrada sella a la anterior). */
final class InMemoryAuditLogRepository implements AuditLogRepositoryInterface
{
    private array $logs = [];
    public function log(DomainEvent $event): void
    {
        $entry = [
            'event_id' => $event->eventId(),
            'event_name' => $event->eventName(),
            'tenant_id' => $event->tenantId(),
            'payload' => $event->payload(),
            'severity' => $event->severity(),
            'occurred_at' => $event->occurredAt()->format('Y-m-d\TH:i:s.uP'),
            'previous_hash' => $this->logs === [] ? '' : $this->logs[array_key_last($this->logs)]['hash'],];
        $entry['hash'] = self::hashOf($entry);
        $this->logs[] = $entry;}
    public function findByTenantId(string $tenantId, int $limit = 100, int $offset = 0): array
    {
        return array_slice(array_values(array_filter($this->logs, fn(array $log) => $log['tenant_id'] === $tenantId)), $offset, $limit);}
    public function findByEventName(string $eventName, int $limit = 100, int $offset = 0): array
    {
        return array_slice(array_values(array_filter($this->logs, fn(array $log) => $log['event_name'] === $eventName)), $offset, $limit);}
    public function verifyHashChain(): bool
    {
        $previous = '';
        foreach ($this->logs as $log) {
            if ($log['previous_hash'] !== $previous || !hash_equals(self::hashOf($log), $log['hash'])) {
                return false;}
            $previous = $log['hash'];}
        return true;}
    public function all(): array
    {
        return $this->logs;}
    private static function hashOf(array $entry): string
    {
        unset($entry['hash']);
        return hash('sha256', json_encode($entry, JSON_THROW_ON_ERROR));}}
