<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use Psr\Http\Message\ServerRequestInterface;
final class RevokeSessionRequest
{
    private ?SessionId $sessionId;
    private ?UserId $targetUserId;
    private string $reason;
    private ?UserId $requesterId;
    private function __construct(
        ?SessionId $sessionId,
        ?UserId $targetUserId,
        string $reason,
        ?UserId $requesterId
    ) {
        $this->sessionId = $sessionId;
        $this->targetUserId = $targetUserId;
        $this->reason = $reason;
        $this->requesterId = $requesterId;
    }
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = $request->getParsedBody();
        $routeParams = $request->getAttributes();
        $authUser = $request->getAttribute('auth_user');
        $requesterId = $authUser ? new UserId($authUser['user_id']) : null;
        $sessionId = null;
        if (isset($data['session_id'])) {
            $sessionId = new SessionId($data['session_id']);
        }
        $targetUserId = null;
        if (isset($routeParams['user_id'])) {
            $targetUserId = new UserId($routeParams['user_id']);
        }
        return new self(
            $sessionId,
            $targetUserId,
            $data['reason'] ?? 'logout',
            $requesterId
        );
    }
    public function sessionId(): ?SessionId { return $this->sessionId; }
    public function targetUserId(): ?UserId { return $this->targetUserId; }
    public function reason(): string { return $this->reason; }
    public function requesterId(): ?UserId { return $this->requesterId; }
}
