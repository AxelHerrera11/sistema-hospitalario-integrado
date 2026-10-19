<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
interface TenantRepositoryInterface
{
    public function save(Tenant $tenant): void;
    public function findById(TenantId $id): ?Tenant;
    public function findAll(int $limit = 50, int $offset = 0): array;
    public function count(): int;}
