<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class InMemoryTenantRepository implements TenantRepositoryInterface
{
    private array $tenants = [];
    public function save(Tenant $tenant): void
    {
        $this->tenants[$tenant->id()->value()] = $tenant;}
    public function findById(TenantId $id): ?Tenant
    {
        return $this->tenants[$id->value()] ?? null;}
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        return array_slice(array_values($this->tenants), $offset, $limit);}
    public function count(): int
    {
        return count($this->tenants);}
    public function clear(): void
    {
        $this->tenants = [];
    }
}
