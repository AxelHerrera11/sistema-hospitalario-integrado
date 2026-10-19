<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
final class RefreshTokenRotator
{
    private RefreshTokenRepositoryInterface $repository;
    private Argon2idHasher $hasher;
    private TokenFactory $tokenFactory;
    private int $refreshTtl;
    public function __construct(
        RefreshTokenRepositoryInterface $repository,
        Argon2idHasher $hasher,
        TokenFactory $tokenFactory,
        int $refreshTtl = 604800
    ) {
        $this->repository = $repository;
        $this->hasher = $hasher;
        $this->tokenFactory = $tokenFactory;
        $this->refreshTtl = $refreshTtl;}
    public function generateAndStore(
        User $user,
        Tenant $tenant,
        DeviceFingerprint $device,
        SessionId $sessionId,
        string $accessTokenJti
    ): RefreshTokenResult {
        $refreshToken = $this->createRefreshToken($user, $tenant, $device, $accessTokenJti);
        $this->repository->save($refreshToken);
        return new RefreshTokenResult($refreshToken->token(), $refreshToken->expiresAt()->getTimestamp());}
    public function rotate(string $refreshToken, User $user, Tenant $tenant, DeviceFingerprint $device, SessionId $sessionId): RotationResult
    {
        $stored = $this->repository->findByUserIdAndDevice($user->id(), $device->deviceId());
        if ($stored === null) {
            return RotationResult::failure('Refresh token revocado o expirado');}
        if (!$this->hasher->verify($refreshToken, $stored->hash())) {
            return RotationResult::reuseDetected($user->id()->value());}
        $this->repository->delete($stored);
        $newAccessToken = $this->tokenFactory->createAccessToken($user, $tenant, $sessionId, $device);
        $newRefreshToken = $this->createRefreshToken($user, $tenant, $device, $newAccessToken->jti());
        $this->repository->save($newRefreshToken);
        return RotationResult::success(
            $newAccessToken->token(),
            $newRefreshToken->token(),
            $newAccessToken->expiresAt(),
            $newRefreshToken->expiresAt()->getTimestamp());}
    private function createRefreshToken(
        User $user,
        Tenant $tenant,
        DeviceFingerprint $device,
        string $accessTokenJti
    ): RefreshToken {
        $token = bin2hex(random_bytes(32));
        $hash = $this->hasher->hash($token);
        $expiresAt = (new \DateTimeImmutable())->modify("+{$this->refreshTtl} seconds");
        $rt = RefreshToken::create($user->id(), $tenant->id(), $device, $accessTokenJti, $this->refreshTtl);
        $reflection = new \ReflectionClass($rt);
        $reflection->getProperty('token')->setValue($rt, $token);
        $reflection->getProperty('hash')->setValue($rt, $hash);
        $reflection->getProperty('expiresAt')->setValue($rt, $expiresAt);
        return $rt;}}
