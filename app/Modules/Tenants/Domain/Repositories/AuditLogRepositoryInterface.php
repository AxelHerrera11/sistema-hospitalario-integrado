<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Events\DomainEvent;
interface AuditLogRepositoryInterface
{
    public function log(DomainEvent $event): void;
    public function findByTenantId(string $tenantId, int $limit = 100, int $offset = 0): array;
    public function findByEventName(string $eventName, int $limit = 100, int $offset = 0): array;}
