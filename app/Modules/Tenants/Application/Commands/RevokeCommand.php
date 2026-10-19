<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Commands;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class RevokeCommand
{
    private ?SessionId $sessionId;
    private ?UserId $targetUserId;
    private string $reason;
    private ?UserId $requesterId;
    public function __construct(
        ?SessionId $sessionId = null,
        ?UserId $targetUserId = null,
        string $reason = 'logout',
        ?UserId $requesterId = null
    ) {
        $this->sessionId = $sessionId;
        $this->targetUserId = $targetUserId;
        $this->reason = $reason;
        $this->requesterId = $requesterId; }
    public function sessionId(): ?SessionId { return $this->sessionId; }
    public function targetUserId(): ?UserId { return $this->targetUserId; }
    public function reason(): string { return $this->reason; }
    public function requesterId(): ?UserId { return $this->requesterId; }
    public function isForceRevoke(): bool
    { return $this->targetUserId !== null; }}
