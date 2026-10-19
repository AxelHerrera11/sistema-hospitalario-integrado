<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
final class Session
{
    private SessionId $id;
    private UserId $userId;
    private TenantId $tenantId;
    private DeviceFingerprint $device;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $lastActivityAt;
    private bool $revoked;
    private function __construct(
        SessionId $id,
        UserId $userId,
        TenantId $tenantId,
        DeviceFingerprint $device
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->device = $device;
        $this->createdAt = new \DateTimeImmutable();
        $this->lastActivityAt = new \DateTimeImmutable();
        $this->revoked = false;}
    public static function create(
        UserId $userId,
        TenantId $tenantId,
        DeviceFingerprint $device
    ): self { return new self(SessionId::generate(), $userId, $tenantId, $device);}
    public static function reconstitute(
        SessionId $id,
        UserId $userId,
        TenantId $tenantId,
        DeviceFingerprint $device,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $lastActivityAt,
        bool $revoked
    ): self {
        $session = new self($id, $userId, $tenantId, $device);
        $reflection = new \ReflectionClass($session);
        $reflection->getProperty('createdAt')->setValue($session, $createdAt);
        $reflection->getProperty('lastActivityAt')->setValue($session, $lastActivityAt);
        $reflection->getProperty('revoked')->setValue($session, $revoked);
        return $session;}
    public function id(): SessionId { return $this->id; }
    public function userId(): UserId { return $this->userId; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function lastActivityAt(): \DateTimeImmutable { return $this->lastActivityAt; }
    public function isRevoked(): bool { return $this->revoked; }
    public function updateActivity(): void
    { $this->lastActivityAt = new \DateTimeImmutable();}
    public function revoke(): void
    { $this->revoked = true;}
    public function isIdle(int $idleTimeoutSeconds): bool
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->lastActivityAt->getTimestamp();
        return $diff > $idleTimeoutSeconds;}
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'device' => $this->device->toArray(),
            'created_at' => $this->createdAt->format('c'),
            'last_activity_at' => $this->lastActivityAt->format('c'),
            'revoked' => $this->revoked,];}}
