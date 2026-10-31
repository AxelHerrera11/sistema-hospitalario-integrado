<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Results;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Services\AccessToken;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
/** Resultado del login: tokens emitidos o un desafío MFA pendiente (sin tokens). */
final class AuthResult
{
    private ?AccessToken $accessToken;
    private ?string $refreshToken;
    private int $expiresIn;
    private User $user;
    private ?SessionId $sessionId;
    private ?string $challengeId;
    private ?string $mfaMethod;
    private function __construct(User $user, ?AccessToken $accessToken, ?string $refreshToken, int $expiresIn, ?SessionId $sessionId, ?string $challengeId, ?string $mfaMethod)
    {
        $this->user = $user;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;
        $this->sessionId = $sessionId;
        $this->challengeId = $challengeId;
        $this->mfaMethod = $mfaMethod;}
    public static function authenticated(User $user, AccessToken $accessToken, string $refreshToken, int $expiresIn, SessionId $sessionId): self
    { return new self($user, $accessToken, $refreshToken, $expiresIn, $sessionId, null, null);}
    public static function mfaChallenge(User $user, string $challengeId, string $method): self
    { return new self($user, null, null, 0, null, $challengeId, $method);}
    public function accessToken(): ?AccessToken { return $this->accessToken; }
    public function refreshToken(): ?string { return $this->refreshToken; }
    public function expiresIn(): int { return $this->expiresIn; }
    public function user(): User { return $this->user; }
    public function sessionId(): ?SessionId { return $this->sessionId; }
    public function isMfaRequired(): bool { return $this->challengeId !== null; }
    public function challengeId(): ?string { return $this->challengeId; }
    public function mfaMethod(): ?string { return $this->mfaMethod; }}
