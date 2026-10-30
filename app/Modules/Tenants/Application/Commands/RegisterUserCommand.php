<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class RegisterUserCommand
{
    private Email $email;
    private string $name;
    private string $rawPassword;
    /** @var Role[] */
    private array $roles;
    private TenantId $tenantId;
    private UserId $createdBy;
    private bool $mfaRequired;
    private ?UserId $existingUserId;
    /** @param array<Role|string> $roles acepta objetos Role o códigos canónicos */
    public function __construct(
        Email $email,
        string $name,
        string $rawPassword,
        array $roles,
        TenantId $tenantId,
        UserId $createdBy,
        bool $mfaRequired = false,
        ?UserId $existingUserId = null
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->rawPassword = $rawPassword;
        $this->roles = array_map(fn(Role|string $r) => $r instanceof Role ? $r : new Role($r), $roles);
        $this->tenantId = $tenantId;
        $this->createdBy = $createdBy;
        $this->mfaRequired = $mfaRequired;
        $this->existingUserId = $existingUserId;}
    public function email(): Email { return $this->email; }
    public function name(): string { return $this->name; }
    public function rawPassword(): string { return $this->rawPassword; }
    /** @return Role[] */
    public function roles(): array { return $this->roles; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function createdBy(): UserId { return $this->createdBy; }
    public function mfaRequired(): bool { return $this->mfaRequired; }
    /** Identidad existente en otro hospital: la nueva membresía reutiliza su UserId. */
    public function existingUserId(): ?UserId { return $this->existingUserId; }}
