<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use Psr\Http\Message\ServerRequestInterface;
final class RegisterUserRequest
{
    private Email $email;
    private string $name;
    private string $password;
    private array $roles;
    private TenantId $tenantId;
    private UserId $createdBy;
    private bool $mfaRequired;
    private function __construct(
        Email $email,
        string $name,
        string $password,
        array $roles,
        TenantId $tenantId,
        UserId $createdBy,
        bool $mfaRequired
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->password = $password;
        $this->roles = $roles;
        $this->tenantId = $tenantId;
        $this->createdBy = $createdBy;
        $this->mfaRequired = $mfaRequired;
    }
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = $request->getParsedBody();
        $tenantHeader = $request->getHeaderLine('X-Tenant-ID');
        if (empty($tenantHeader)) {
            throw new \InvalidArgumentException('X-Tenant-ID header requerido');
        }
        $authUser = $request->getAttribute('auth_user');
        if ($authUser === null) {
            throw new \InvalidArgumentException('Usuario no autenticado');
        }
        return new self(
            new Email($data['email'] ?? ''),
            $data['name'] ?? '',
            $data['password'] ?? '',
            array_map(fn(string $r) => new Role($r), $data['roles'] ?? []),
            new TenantId($tenantHeader),
            new UserId($authUser['user_id']),
            (bool)($data['mfa_required'] ?? false)
        );
    }
    public function email(): Email { return $this->email; }
    public function name(): string { return $this->name; }
    public function password(): string { return $this->password; }
    public function roles(): array { return $this->roles; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function createdBy(): UserId { return $this->createdBy; }
    public function mfaRequired(): bool { return $this->mfaRequired; }
}
