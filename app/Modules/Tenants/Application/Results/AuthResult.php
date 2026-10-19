<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Results;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\Services\AccessToken;
final class AuthResult
{
    private AccessToken $accessToken;
    private string $refreshToken;
    private int $expiresIn;
    private User $user;
    private SessionId $sessionId;
    private bool $mfaRequired;
    private ?string $challengeId;
    private ?string $mfaMethod;
    public function __construct(
        AccessToken $accessToken,
        string $refreshToken,
        int $expiresIn,
        User $user,
        SessionId $sessionId,
        bool $mfaRequired = false,
        ?string $challengeId = null,
        ?string $mfaMethod = null
    ) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;
        $this->user = $user;
        $this->sessionId = $sessionId;
        $this->mfaRequired = $mfaRequired;
        $this->challengeId = $challengeId;
        $this->mfaMethod = $mfaMethod;}
    public function accessToken(): AccessToken { return $this->accessToken; }
    public function refreshToken(): string { return $this->refreshToken; }
    public function expiresIn(): int { return $this->expiresIn; }
    public function user(): User { return $this->user; }
    public function sessionId(): SessionId { return $this->sessionId; }
    public function isMfaRequired(): bool { return $this->mfaRequired; }
    public function challengeId(): ?string { return $this->challengeId; }
    public function mfaMethod(): ?string { return $this->mfaMethod; }}
