<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    private array $memberships = [];
    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;
        $tenantId = $user->tenantId()->value();
        $userId = $user->id()->value();
        if (!isset($this->memberships[$userId])) {
            $this->memberships[$userId] = [];}
        if (!isset($this->memberships[$userId][$tenantId])) {
            $this->memberships[$userId][$tenantId] = [];}
        foreach ($user->roles() as $role) {
            if (!in_array($role->value(), $this->memberships[$userId][$tenantId], true)) {
                $this->memberships[$userId][$tenantId][] = $role->value();}}}
    public function findById(UserId $id): ?User
    {
        return $this->users[$id->value()] ?? null;}
    public function findByEmailAndTenant(Email $email, TenantId $tenantId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email) && $user->tenantId()->equals($tenantId)) {
                return $user;}}
        return null;}
    public function findByIdAndTenant(UserId $id, TenantId $tenantId): ?User
    {
        $user = $this->users[$id->value()] ?? null;
        return $user && $user->tenantId()->equals($tenantId) ? $user : null;}
    public function existsByEmailAndTenant(Email $email, TenantId $tenantId): bool
    {
        return $this->findByEmailAndTenant($email, $tenantId) !== null;}
    public function findAllByTenant(TenantId $tenantId, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter(
            $this->users,
            fn(User $u) => $u->tenantId()->equals($tenantId));
        return array_slice(array_values($filtered), $offset, $limit);}
    public function countByTenant(TenantId $tenantId): int
    {
        return count(array_filter($this->users, fn(User $u) => $u->tenantId()->equals($tenantId)));}
    public function delete(UserId $id): void
    {
        unset($this->users[$id->value()], $this->memberships[$id->value()]);}
    public function clear(): void
    {
        $this->users = [];
        $this->memberships = [];
    }
}
