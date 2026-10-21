<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use Psr\Http\Message\ServerRequestInterface;
/** El hospital destino sale del token del administrador, no de un dato que envía el cliente. */
final class RegisterUserRequest
{
    /** @param string[] $roles códigos canónicos; RegisterUserCommand los convierte en Role */
    private function __construct(
        private Email $email,
        private string $name,
        private string $password,
        private array $roles,
        private TenantId $tenantId,
        private UserId $createdBy,
        private bool $mfaRequired,
        private ?UserId $existingUserId
    ) {}
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $auth = $request->getAttribute('auth');
        if (!$auth instanceof JwtClaims) {
            throw new \DomainException('Usuario no autenticado', 401);}
        $data = (array) $request->getParsedBody();
        $roles = $data['roles'] ?? [];
        if (!is_array($roles) || $roles === [] || array_filter($roles, 'is_string') !== $roles) {
            throw new \InvalidArgumentException('roles debe ser una lista de códigos de rol');}
        return new self(
            new Email((string) ($data['email'] ?? '')),
            (string) ($data['name'] ?? ''),
            (string) ($data['password'] ?? ''),
            array_values($roles),
            new TenantId($auth->tenantId()),
            new UserId($auth->subject()),
            (bool) ($data['mfa_required'] ?? false),
            isset($data['user_id']) ? new UserId((string) $data['user_id']) : null);}
    public function email(): Email { return $this->email; }
    public function name(): string { return $this->name; }
    public function password(): string { return $this->password; }
    public function roles(): array { return $this->roles; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function createdBy(): UserId { return $this->createdBy; }
    public function mfaRequired(): bool { return $this->mfaRequired; }
    public function existingUserId(): ?UserId { return $this->existingUserId; }}
