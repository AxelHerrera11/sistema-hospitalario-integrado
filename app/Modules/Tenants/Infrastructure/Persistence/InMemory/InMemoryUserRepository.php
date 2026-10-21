<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
/** Indexa por hospital y usuario: guardar la membresía de un hospital no reemplaza la de otro. */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, array<string, User>> tenant_id => user_id => User */
    private array $memberships = [];
    public function save(User $user): void
    {
        $this->memberships[$user->tenantId()->value()][$user->id()->value()] = $user;}
    public function findByIdAndTenant(UserId $id, TenantId $tenantId): ?User
    {
        return $this->memberships[$tenantId->value()][$id->value()] ?? null;}
    public function findByEmailAndTenant(Email $email, TenantId $tenantId): ?User
    {
        foreach ($this->memberships[$tenantId->value()] ?? [] as $user) {
            if ($user->email()->equals($email)) {
                return $user;}}
        return null;}
    public function existsByEmailAndTenant(Email $email, TenantId $tenantId): bool
    {
        return $this->findByEmailAndTenant($email, $tenantId) !== null;}
    public function findAllByTenant(TenantId $tenantId, int $limit = 50, int $offset = 0): array
    {
        return array_slice(array_values($this->memberships[$tenantId->value()] ?? []), $offset, $limit);}
    public function countByTenant(TenantId $tenantId): int
    {
        return count($this->memberships[$tenantId->value()] ?? []);}}
