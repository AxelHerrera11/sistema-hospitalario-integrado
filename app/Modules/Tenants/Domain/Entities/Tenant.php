<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
final class Tenant
{
    private TenantId $id;
    private string $name;
    private string $status;
    private int $userQuota;
    private int $usersCount;
    private array $policies;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_DELETED = 'deleted';
    private function __construct(
        TenantId $id,
        string $name,
        string $status = self::STATUS_ACTIVE,
        int $userQuota = 100,
        int $usersCount = 0,
        array $policies = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->userQuota = $userQuota;
        $this->usersCount = $usersCount;
        $this->policies = array_merge([
            'mfa_required' => false,
            'single_session' => false,
            'password_min_length' => 12,
            'password_require_complexity' => true,
            'password_check_hibp' => true,
            'session_idle_timeout' => 900,
            'max_login_attempts' => 5,
            'lockout_duration_1' => 900,
            'lockout_duration_2' => 3600,
            'lockout_duration_3' => 86400,
        ], $policies);
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();}
    public static function create(TenantId $id, string $name, int $userQuota = 100, array $policies = []): self
    { return new self($id, $name, self::STATUS_ACTIVE, $userQuota, 0, $policies);}
    public static function reconstitute(
        TenantId $id,
        string $name,
        string $status,
        int $userQuota,
        int $usersCount,
        array $policies,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt
    ): self {
        $tenant = new self($id, $name, $status, $userQuota, $usersCount, $policies);
        $reflection = new \ReflectionClass($tenant);
        $reflection->getProperty('createdAt')->setValue($tenant, $createdAt);
        $reflection->getProperty('updatedAt')->setValue($tenant, $updatedAt);
        return $tenant;}
    public function id(): TenantId { return $this->id; }
    public function name(): string { return $this->name; }
    public function status(): string { return $this->status; }
    public function userQuota(): int { return $this->userQuota; }
    public function usersCount(): int { return $this->usersCount; }
    public function policies(): array { return $this->policies; }
    public function policy(string $key, mixed $default = null): mixed { return $this->policies[$key] ?? $default; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function isActive(): bool
    { return $this->status === self::STATUS_ACTIVE;}
    public function hasQuota(): bool
    { return $this->usersCount < $this->userQuota;}
    public function incrementUsersCount(): void
    {
        $this->usersCount++;
        $this->updatedAt = new \DateTimeImmutable();}
    public function decrementUsersCount(): void
    {
        if ($this->usersCount > 0) {
            $this->usersCount--;
            $this->updatedAt = new \DateTimeImmutable();}}
    public function updatePolicy(string $key, mixed $value): void
    {
        $this->policies[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();}
    public function suspend(): void
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->updatedAt = new \DateTimeImmutable();}
    public function activate(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();}
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name,
            'status' => $this->status,
            'user_quota' => $this->userQuota,
            'users_count' => $this->usersCount,
            'policies' => $this->policies,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),];}}
