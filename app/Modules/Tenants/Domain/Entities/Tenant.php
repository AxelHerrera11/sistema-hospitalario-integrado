<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use DateTimeImmutable;
/** Hospital del micro-HIS. Cuota y políticas viven solo en memoria: no se mapean a la tabla hospitals. */
final class Tenant
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    private const DEFAULT_POLICIES = [
        'mfa_required' => false,
        'single_session' => false,
        'session_idle_timeout' => 900,
        'max_login_attempts' => 5,
        'lockout_duration_1' => 900,
        'lockout_duration_2' => 3600,
        'lockout_duration_3' => 86400,];
    private TenantId $id;
    private string $name;
    private string $status = self::STATUS_ACTIVE;
    private int $userQuota;
    private int $usersCount = 0;
    private array $policies;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private function __construct(TenantId $id, string $name, int $userQuota, array $policies)
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('El nombre del hospital es obligatorio');}
        if ($userQuota < 1) {
            throw new \InvalidArgumentException('La cuota de usuarios debe ser mayor que cero');}
        $this->id = $id;
        $this->name = trim($name);
        $this->userQuota = $userQuota;
        $this->policies = array_merge(self::DEFAULT_POLICIES, array_intersect_key($policies, self::DEFAULT_POLICIES));
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();}
    public static function create(TenantId $id, string $name, int $userQuota = 100, array $policies = []): self
    { return new self($id, $name, $userQuota, $policies);}
    public function id(): TenantId { return $this->id; }
    public function name(): string { return $this->name; }
    public function status(): string { return $this->status; }
    public function userQuota(): int { return $this->userQuota; }
    public function usersCount(): int { return $this->usersCount; }
    public function policies(): array { return $this->policies; }
    public function policy(string $key, mixed $default = null): mixed { return $this->policies[$key] ?? $default; }
    public function isActive(): bool
    { return $this->status === self::STATUS_ACTIVE;}
    public function hasQuota(): bool
    { return $this->usersCount < $this->userQuota;}
    /** Escalonado: 5 fallos → 15 min, 10 → 1 h, 20 → 24 h (configurable por hospital). */
    public function lockoutDuration(int $failedAttempts): int
    {
        if ($failedAttempts >= 20) return (int) $this->policies['lockout_duration_3'];
        if ($failedAttempts >= 10) return (int) $this->policies['lockout_duration_2'];
        return (int) $this->policies['lockout_duration_1'];}
    public function incrementUsersCount(): void
    {
        $this->usersCount++;
        $this->updatedAt = new DateTimeImmutable();}
    public function suspend(): void
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->updatedAt = new DateTimeImmutable();}
    public function activate(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->updatedAt = new DateTimeImmutable();}
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
