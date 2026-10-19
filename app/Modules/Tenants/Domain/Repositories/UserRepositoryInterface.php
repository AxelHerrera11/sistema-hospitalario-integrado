<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmailAndTenant(Email $email, TenantId $tenantId): ?User;
    public function findByIdAndTenant(UserId $id, TenantId $tenantId): ?User;
    public function existsByEmailAndTenant(Email $email, TenantId $tenantId): bool;
    public function findAllByTenant(TenantId $tenantId, int $limit = 50, int $offset = 0): array;
    public function countByTenant(TenantId $tenantId): int;
    public function delete(UserId $id): void;}
