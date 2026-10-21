<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
/** Revocación siempre acotada a un hospital: la propia sesión o todas las de otro usuario. */
final class RevokeCommand
{
    private TenantId $tenantId;
    private UserId $requesterId;
    private ?SessionId $sessionId;
    private ?UserId $targetUserId;
    private string $reason;
    public function __construct(
        TenantId $tenantId,
        UserId $requesterId,
        ?SessionId $sessionId = null,
        ?UserId $targetUserId = null,
        string $reason = 'logout'
    ) {
        if (($sessionId === null) === ($targetUserId === null)) {
            throw new \InvalidArgumentException('Indique una sesión propia o un usuario destino, no ambos');}
        $this->tenantId = $tenantId;
        $this->requesterId = $requesterId;
        $this->sessionId = $sessionId;
        $this->targetUserId = $targetUserId;
        $this->reason = $reason;}
    public function tenantId(): TenantId { return $this->tenantId; }
    public function requesterId(): UserId { return $this->requesterId; }
    public function sessionId(): ?SessionId { return $this->sessionId; }
    public function targetUserId(): ?UserId { return $this->targetUserId; }
    public function reason(): string { return $this->reason; }
    public function isForceRevoke(): bool
    { return $this->targetUserId !== null; }}
