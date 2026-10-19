<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class RefreshToken
{
    private string $token;
    private string $hash;
    private UserId $userId;
    private TenantId $tenantId;
    private DeviceFingerprint $device;
    private string $accessTokenJti;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $expiresAt;
    private bool $revoked;
    private function __construct(
        string $token,
        string $hash,
        UserId $userId,
        TenantId $tenantId,
        DeviceFingerprint $device,
        string $accessTokenJti,
        \DateTimeImmutable $expiresAt
    ) {
        $this->token = $token;
        $this->hash = $hash;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->device = $device;
        $this->accessTokenJti = $accessTokenJti;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
        $this->revoked = false;
    }
    public static function create(
        UserId $userId,
        TenantId $tenantId,
        DeviceFingerprint $device,
        string $accessTokenJti,
        int $ttlSeconds = 604800
    ): self {
        $token = bin2hex(random_bytes(32));
        return new self(
            $token,
            '',
            $userId,
            $tenantId,
            $device,
            $accessTokenJti,
            (new \DateTimeImmutable())->modify("+{$ttlSeconds} seconds"));}
    public static function reconstitute(
        string $token,
        string $hash,
        UserId $userId,
        TenantId $tenantId,
        DeviceFingerprint $device,
        string $accessTokenJti,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
        bool $revoked
    ): self {
        $rt = new self($token, $hash, $userId, $tenantId, $device, $accessTokenJti, $expiresAt);
        $reflection = new \ReflectionClass($rt);
        $reflection->getProperty('createdAt')->setValue($rt, $createdAt);
        $reflection->getProperty('revoked')->setValue($rt, $revoked);
        return $rt;}
    public function token(): string { return $this->token; }
    public function hash(): string { return $this->hash; }
    public function userId(): UserId { return $this->userId; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function accessTokenJti(): string { return $this->accessTokenJti; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function expiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function isRevoked(): bool { return $this->revoked; }
    public function setHash(string $hash): void
    { $this->hash = $hash;}
    public function revoke(): void
    { $this->revoked = true;}
    public function isExpired(): bool
    { return new \DateTimeImmutable() >= $this->expiresAt;}
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'hash' => $this->hash,
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'device' => $this->device->toArray(),
            'access_token_jti' => $this->accessTokenJti,
            'created_at' => $this->createdAt->format('c'),
            'expires_at' => $this->expiresAt->format('c'),
            'revoked' => $this->revoked,];}}
