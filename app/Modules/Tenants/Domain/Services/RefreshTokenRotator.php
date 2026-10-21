<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
/** Emite y rota refresh tokens de un solo uso; presentar uno ya rotado se trata como robo. */
final class RefreshTokenRotator
{
    private RefreshTokenRepositoryInterface $repository;
    private int $refreshTtl;
    public function __construct(RefreshTokenRepositoryInterface $repository, int $refreshTtl = 604800)
    {
        $this->repository = $repository;
        $this->refreshTtl = $refreshTtl;}
    public function issue(TenantId $tenantId, UserId $userId, SessionId $sessionId, DeviceFingerprint $device): RefreshTokenResult
    {
        [$refreshToken, $raw] = RefreshToken::issue($tenantId, $userId, $sessionId, $device, $this->refreshTtl);
        $this->repository->save($refreshToken);
        return new RefreshTokenResult($raw, $refreshToken->expiresAt()->getTimestamp());}
    public function rotate(string $rawToken): RotationResult
    {
        $parsed = RefreshToken::parse($rawToken);
        $stored = $parsed === null ? null : $this->repository->findById($parsed[0]);
        if ($stored === null || !$stored->matches($parsed[1])) {
            return RotationResult::failure('Refresh token inválido o revocado');}
        if ($stored->isRotated()) {
            return RotationResult::reuseDetected($stored);}
        if ($stored->isRevoked() || $stored->isExpired()) {
            return RotationResult::failure('Refresh token inválido o revocado');}
        $stored->markRotated();
        $this->repository->save($stored);
        $next = $this->issue($stored->tenantId(), $stored->userId(), $stored->sessionId(), $stored->device());
        return RotationResult::success($stored, $next);}}
