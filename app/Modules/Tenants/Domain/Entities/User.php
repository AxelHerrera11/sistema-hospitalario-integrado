<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Password;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use Ramsey\Uuid\Uuid;
final class User
{
    private UserId $id;
    private Email $email;
    private Password $password;
    private string $name;
    private array $roles;
    private TenantId $tenantId;
    private string $status;
    private ?string $totpSecret;
    private bool $mfaRequired;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $lastLoginAt;
    private ?\DateTimeImmutable $passwordChangedAt;
    private array $passwordHistory;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_LOCKED = 'locked';
    private function __construct(
        UserId $id,
        Email $email,
        Password $password,
        string $name,
        array $roles,
        TenantId $tenantId,
        string $status = self::STATUS_ACTIVE,
        ?string $totpSecret = null,
        bool $mfaRequired = false
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->roles = $roles;
        $this->tenantId = $tenantId;
        $this->status = $status;
        $this->totpSecret = $totpSecret;
        $this->mfaRequired = $mfaRequired;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->lastLoginAt = null;
        $this->passwordChangedAt = new \DateTimeImmutable();
        $this->passwordHistory = [$password->hash()];}
    public static function create(
        Email $email,
        Password $password,
        string $name,
        array $roles,
        TenantId $tenantId,
        ?string $totpSecret = null,
        bool $mfaRequired = false
    ): self {
        $roleObjects = array_map(fn(string $r) => new Role($r), $roles);
        return new self(
            UserId::generate(),
            $email,
            $password,
            $name,
            $roleObjects,
            $tenantId,
            self::STATUS_ACTIVE,
            $totpSecret,
            $mfaRequired);}
    public static function reconstitute(
        UserId $id,
        Email $email,
        Password $password,
        string $name,
        array $roles,
        TenantId $tenantId,
        string $status,
        ?string $totpSecret,
        bool $mfaRequired,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $lastLoginAt,
        ?\DateTimeImmutable $passwordChangedAt,
        array $passwordHistory
    ): self {
        $user = new self($id, $email, $password, $name, $roles, $tenantId, $status, $totpSecret, $mfaRequired);
        $reflection = new \ReflectionClass($user);
        $reflection->getProperty('createdAt')->setValue($user, $createdAt);
        $reflection->getProperty('updatedAt')->setValue($user, $updatedAt);
        $reflection->getProperty('lastLoginAt')->setValue($user, $lastLoginAt);
        $reflection->getProperty('passwordChangedAt')->setValue($user, $passwordChangedAt);
        $reflection->getProperty('passwordHistory')->setValue($user, $passwordHistory);
        return $user;}
    public function id(): UserId { return $this->id; }
    public function email(): Email { return $this->email; }
    public function password(): Password { return $this->password; }
    public function name(): string { return $this->name; }
    public function roles(): array { return $this->roles; }
    public function roleValues(): array { return array_map(fn(Role $r) => $r->value(), $this->roles); }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function status(): string { return $this->status; }
    public function totpSecret(): ?string { return $this->totpSecret; }
    public function mfaRequired(): bool { return $this->mfaRequired; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function lastLoginAt(): ?\DateTimeImmutable { return $this->lastLoginAt; }
    public function passwordChangedAt(): \DateTimeImmutable { return $this->passwordChangedAt; }
    public function passwordHistory(): array { return $this->passwordHistory; }
    public function hasRole(string $role): bool
    {return in_array(strtoupper($role), $this->roleValues(), true);}
    public function isActive(): bool
    {return $this->status === self::STATUS_ACTIVE;}
    public function isLocked(): bool
    {return $this->status === self::STATUS_LOCKED;}
    public function lock(): void
    {
        $this->status = self::STATUS_LOCKED;
        $this->updatedAt = new \DateTimeImmutable();}
    public function unlock(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();}
    public function deactivate(): void
    {
        $this->status = self::STATUS_INACTIVE;
        $this->updatedAt = new \DateTimeImmutable();}
    public function recordLogin(DeviceFingerprint $device): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();}
    public function changePassword(Password $newPassword): void
    {
        $this->password = $newPassword;
        $this->passwordHistory[] = $newPassword->hash();
        if (count($this->passwordHistory) > 5) {
            array_shift($this->passwordHistory);}
        $this->passwordChangedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();}
    public function setTotpSecret(string $secret): void
    {
        $this->totpSecret = $secret;
        $this->updatedAt = new \DateTimeImmutable();}
    public function enableMfa(): void
    {
        $this->mfaRequired = true;
        $this->updatedAt = new \DateTimeImmutable();}
    public function disableMfa(): void
    {
        $this->mfaRequired = false;
        $this->updatedAt = new \DateTimeImmutable();}
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'email' => $this->email->value(),
            'name' => $this->name,
            'roles' => $this->roleValues(),
            'tenant_id' => $this->tenantId->value(),
            'status' => $this->status,
            'mfa_required' => $this->mfaRequired,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'last_login_at' => $this->lastLoginAt?->format('c'),
            'password_changed_at' => $this->passwordChangedAt->format('c'),];}}
