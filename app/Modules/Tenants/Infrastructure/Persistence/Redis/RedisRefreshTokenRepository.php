<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\Redis;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use Redis;
use RedisException;
final class RedisRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private Redis $redis;
    private int $defaultTtl;
    public function __construct(Redis $redis, int $defaultTtl = 604800)
    {
        $this->redis = $redis;
        $this->defaultTtl = $defaultTtl;}
    public function save(RefreshToken $refreshToken): void
    {
        $key = $this->getKey($refreshToken->userId()->value(), $refreshToken->device()->deviceId());
        $data = json_encode([
            'token' => $refreshToken->token(),
            'hash' => $refreshToken->hash(),
            'user_id' => $refreshToken->userId()->value(),
            'tenant_id' => $refreshToken->tenantId()->value(),
            'device' => $refreshToken->device()->toArray(),
            'access_token_jti' => $refreshToken->accessTokenJti(),
            'created_at' => $refreshToken->createdAt()->format('c'),
            'expires_at' => $refreshToken->expiresAt()->format('c'),
            'revoked' => $refreshToken->isRevoked(),]);
        try {
            $this->redis->setex($key, $this->defaultTtl, $data);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error guardando refresh token: ' . $e->getMessage());}}
    public function findByUserIdAndDevice(UserId $userId, string $deviceId): ?RefreshToken
    {
        $key = $this->getKey($userId->value(), $deviceId);
        try {
            $data = $this->redis->get($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error buscando refresh token: ' . $e->getMessage());}
        if ($data === false) {
            return null;}
        return $this->mapDataToRefreshToken(json_decode($data, true));}
    public function findAllByUserId(UserId $userId): array
    {
        $pattern = "refresh:{$userId->value()}:*";
        try {
            $keys = $this->redis->keys($pattern);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error listando refresh tokens: ' . $e->getMessage());}
        $tokens = [];
        foreach ($keys as $key) {
            $data = $this->redis->get($key);
            if ($data !== false) {
                $tokens[] = $this->mapDataToRefreshToken(json_decode($data, true));}}
        return $tokens;}
    public function delete(RefreshToken $refreshToken): void
    {
        $key = $this->getKey($refreshToken->userId()->value(), $refreshToken->device()->deviceId());
        try {
            $this->redis->del($key);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error eliminando refresh token: ' . $e->getMessage());}}
    public function deleteAllByUserId(UserId $userId): void
    {
        $pattern = "refresh:{$userId->value()}:*";
        try {
            $keys = $this->redis->keys($pattern);
            if (!empty($keys)) {
                $this->redis->del(...$keys);}
        } catch (RedisException $e) {
            throw new \RuntimeException('Error eliminando refresh tokens: ' . $e->getMessage());}}
    public function findByTokenPrefix(string $tokenPrefix): ?RefreshToken
    {
        $pattern = "refresh:*:*";
        try {
            $keys = $this->redis->keys($pattern);
        } catch (RedisException $e) {
            throw new \RuntimeException('Error buscando por prefijo: ' . $e->getMessage());}
        foreach ($keys as $key) {
            $data = $this->redis->get($key);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                if (str_starts_with($decoded['token'], $tokenPrefix)) {
                    return $this->mapDataToRefreshToken($decoded);}}}
        return null;}
    private function getKey(string $userId, string $deviceId): string
    {
        return "refresh:{$userId}:{$deviceId}";}
    private function mapDataToRefreshToken(array $data): RefreshToken
    {
        $device = new DeviceFingerprint(
            $data['device']['device_id'],
            $data['device']['ip'],
            $data['device']['user_agent'],
            $data['device']['trust_score'],
            $data['device']['is_known']);
        return RefreshToken::reconstitute(
            $data['token'],
            $data['hash'],
            UserId::fromString($data['user_id']),
            TenantId::fromString($data['tenant_id']),
            $device,
            $data['access_token_jti'],
            new \DateTimeImmutable($data['created_at']),
            new \DateTimeImmutable($data['expires_at']),
            (bool)$data['revoked']);}}
