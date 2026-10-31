<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
/** Guarda las entidades por id; cada una lleva su hospital, así que no colisionan entre hospitales. */
final class InMemoryRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /** @var array<string, RefreshToken> */
    private array $tokens = [];
    public function save(RefreshToken $refreshToken): void
    {
        $this->tokens[$refreshToken->id()] = $refreshToken;}
    public function findById(string $id): ?RefreshToken
    {
        return $this->tokens[$id] ?? null;}
    public function revokeBySession(SessionId $sessionId): void
    {
        foreach ($this->tokens as $token) {
            if ($token->sessionId()->equals($sessionId)) {
                $token->revoke();}}}
    public function revokeAllByUser(TenantId $tenantId, UserId $userId): void
    {
        foreach ($this->tokens as $token) {
            if ($token->tenantId()->equals($tenantId) && $token->userId()->equals($userId)) {
                $token->revoke();}}}}
