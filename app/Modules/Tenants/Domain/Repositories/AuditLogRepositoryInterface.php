<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Events\DomainEvent;
interface AuditLogRepositoryInterface
{
    public function log(DomainEvent $event): void;
    public function logRaw(string $eventName, array $payload, ?string $severity = null): void;
    public function findByUserId(string $userId, int $limit = 100, int $offset = 0): array;
    public function findByTenantId(string $tenantId, int $limit = 100, int $offset = 0): array;
    public function findByEventName(string $eventName, int $limit = 100, int $offset = 0): array;
    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 100, int $offset = 0): array;}
