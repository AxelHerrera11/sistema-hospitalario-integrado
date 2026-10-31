<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use Psr\Http\Message\ServerRequestInterface;
/**
 * Sin {userId} en la ruta revoca la sesión del propio token (logout);
 * con DELETE /admin/users/{userId}/sessions revoca todas las del usuario destino.
 */
final class RevokeSessionRequest
{
    private function __construct(
        private TenantId $tenantId,
        private UserId $requesterId,
        private ?SessionId $sessionId,
        private ?UserId $targetUserId,
        private string $reason
    ) {}
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $auth = $request->getAttribute('auth');
        if (!$auth instanceof JwtClaims) {
            throw new \DomainException('Usuario no autenticado', 401);}
        $data = (array) $request->getParsedBody();
        $target = $request->getAttribute('userId');
        return new self(
            new TenantId($auth->tenantId()),
            new UserId($auth->subject()),
            $target === null ? new SessionId($auth->sessionId()) : null,
            $target === null ? null : new UserId((string) $target),
            (string) ($data['reason'] ?? ($target === null ? 'logout' : 'admin_revoke')));}
    public function tenantId(): TenantId { return $this->tenantId; }
    public function requesterId(): UserId { return $this->requesterId; }
    public function sessionId(): ?SessionId { return $this->sessionId; }
    public function targetUserId(): ?UserId { return $this->targetUserId; }
    public function reason(): string { return $this->reason; }}
