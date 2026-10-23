<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Password;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use DateTimeImmutable;
/** Membresía de un usuario en un hospital: los roles y el bloqueo no se comparten entre hospitales. */
final class User
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    private const PASSWORD_HISTORY_SIZE = 5;
    private UserId $id;
    private Email $email;
    private Password $password;
    private string $name;
    /** @var Role[] */
    private array $roles;
    private TenantId $tenantId;
    private string $status = self::STATUS_ACTIVE;
    private ?string $totpSecret;
    private bool $mfaRequired;
    private int $failedLoginAttempts = 0;
    private ?DateTimeImmutable $lockedUntil = null;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $lastLoginAt = null;
    private DateTimeImmutable $passwordChangedAt;
    private array $passwordHistory;
    /** @param Role[] $roles */
    private function __construct(UserId $id, Email $email, Password $password, string $name, array $roles, TenantId $tenantId, ?string $totpSecret, bool $mfaRequired)
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('El nombre del usuario es obligatorio');}
        if ($roles === []) {
            throw new \InvalidArgumentException('El usuario necesita al menos un rol');}
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->name = trim($name);
        $this->roles = array_values($roles);
        $this->tenantId = $tenantId;
        $this->totpSecret = $totpSecret;
        $this->mfaRequired = $mfaRequired;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->passwordChangedAt = new DateTimeImmutable();
        $this->passwordHistory = [$password->hash()];}
    /**
     * @param Role[] $roles
     * @param UserId|null $id el UserId de la identidad cuando ya existe en otro hospital
     */
    public static function create(Email $email, Password $password, string $name, array $roles, TenantId $tenantId, ?string $totpSecret = null, bool $mfaRequired = false, ?UserId $id = null): self
    {
        return new self($id ?? UserId::generate(), $email, $password, $name, $roles, $tenantId, $totpSecret, $mfaRequired);}
    public function id(): UserId { return $this->id; }
    public function email(): Email { return $this->email; }
    public function password(): Password { return $this->password; }
    public function name(): string { return $this->name; }
    /** @return Role[] */
    public function roles(): array { return $this->roles; }
    public function roleValues(): array { return array_map(fn(Role $r) => $r->value(), $this->roles); }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function status(): string { return $this->status; }
    public function totpSecret(): ?string { return $this->totpSecret; }
    public function mfaRequired(): bool { return $this->mfaRequired; }
    public function failedLoginAttempts(): int { return $this->failedLoginAttempts; }
    public function lastLoginAt(): ?DateTimeImmutable { return $this->lastLoginAt; }
    public function passwordHistory(): array { return $this->passwordHistory; }
    public function hasRole(string $role): bool
    {return in_array($role, $this->roleValues(), true);}
    public function isActive(): bool
    {return $this->status === self::STATUS_ACTIVE;}
    public function isLocked(): bool
    {return $this->lockedUntil !== null && $this->lockedUntil > new DateTimeImmutable();}
    /** @return int intentos fallidos acumulados */
    public function registerFailedLogin(): int
    {
        $this->failedLoginAttempts++;
        $this->updatedAt = new DateTimeImmutable();
        return $this->failedLoginAttempts;}
    public function lockFor(int $seconds): void
    {
        $this->lockedUntil = (new DateTimeImmutable())->modify("+{$seconds} seconds");
        $this->updatedAt = new DateTimeImmutable();}
    public function clearFailedLogins(): void
    {
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;}
    public function deactivate(): void
    {
        $this->status = self::STATUS_INACTIVE;
        $this->updatedAt = new DateTimeImmutable();}
    public function recordLogin(): void
    {
        $this->lastLoginAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();}
    public function changePassword(Password $newPassword): void
    {
        $this->password = $newPassword;
        $this->passwordHistory[] = $newPassword->hash();
        $this->passwordHistory = array_slice($this->passwordHistory, -self::PASSWORD_HISTORY_SIZE);
        $this->passwordChangedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();}
    public function enrollTotp(string $secret): void
    {
        $this->totpSecret = $secret;
        $this->mfaRequired = true;
        $this->updatedAt = new DateTimeImmutable();}
    /** Datos públicos: sin hash de contraseña ni secreto TOTP. */
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
            'last_login_at' => $this->lastLoginAt?->format('c'),
            'password_changed_at' => $this->passwordChangedAt->format('c'),];}}
