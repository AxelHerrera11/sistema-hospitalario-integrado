<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
/**
 * Refresh token de un solo uso. El cliente recibe "{id}.{secreto}" una única vez;
 * la entidad solo conserva el SHA-256 del secreto (256 bits aleatorios, no necesita Argon2id).
 */
final class RefreshToken
{
    private string $id;
    private TenantId $tenantId;
    private UserId $userId;
    private SessionId $sessionId;
    private DeviceFingerprint $device;
    private string $secretHash;
    private DateTimeImmutable $expiresAt;
    private bool $rotated = false;
    private bool $revoked = false;
    private function __construct(string $id, TenantId $tenantId, UserId $userId, SessionId $sessionId, DeviceFingerprint $device, string $secretHash, DateTimeImmutable $expiresAt)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->device = $device;
        $this->secretHash = $secretHash;
        $this->expiresAt = $expiresAt;}
    /** @return array{0: self, 1: string} la entidad y el token en claro que se entrega al cliente */
    public static function issue(TenantId $tenantId, UserId $userId, SessionId $sessionId, DeviceFingerprint $device, int $ttlSeconds): array
    {
        $id = Uuid::uuid4()->toString();
        $secret = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable())->modify("+{$ttlSeconds} seconds");
        return [new self($id, $tenantId, $userId, $sessionId, $device, hash('sha256', $secret), $expiresAt), $id . '.' . $secret];}
    /** @return array{0: string, 1: string}|null [id, secreto] */
    public static function parse(string $raw): ?array
    {
        $parts = explode('.', $raw, 2);
        if (count($parts) !== 2 || !Uuid::isValid($parts[0]) || $parts[1] === '') {
            return null;}
        return $parts;}
    public function matches(string $secret): bool
    { return hash_equals($this->secretHash, hash('sha256', $secret));}
    public function id(): string { return $this->id; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function userId(): UserId { return $this->userId; }
    public function sessionId(): SessionId { return $this->sessionId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function expiresAt(): DateTimeImmutable { return $this->expiresAt; }
    public function isRotated(): bool { return $this->rotated; }
    public function isRevoked(): bool { return $this->revoked; }
    public function isExpired(): bool { return new DateTimeImmutable() >= $this->expiresAt; }
    public function markRotated(): void { $this->rotated = true; }
    public function revoke(): void { $this->revoked = true; }
    /** Representación persistible: nunca incluye el secreto en claro. */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId->value(),
            'user_id' => $this->userId->value(),
            'session_id' => $this->sessionId->value(),
            'device_id' => $this->device->deviceId(),
            'secret_hash' => $this->secretHash,
            'expires_at' => $this->expiresAt->format('c'),
            'rotated' => $this->rotated,
            'revoked' => $this->revoked,];}}
