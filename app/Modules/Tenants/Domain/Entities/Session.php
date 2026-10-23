<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use DateTimeImmutable;
/** Sesión autenticada. Su id es el claim session_id del JWT: revocarla invalida sus access tokens. */
final class Session
{
    private SessionId $id;
    private UserId $userId;
    private TenantId $tenantId;
    private DeviceFingerprint $device;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $lastActivityAt;
    private bool $revoked = false;
    private function __construct(SessionId $id, UserId $userId, TenantId $tenantId, DeviceFingerprint $device)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->device = $device;
        $this->createdAt = new DateTimeImmutable();
        $this->lastActivityAt = new DateTimeImmutable();}
    public static function start(SessionId $id, UserId $userId, TenantId $tenantId, DeviceFingerprint $device): self
    { return new self($id, $userId, $tenantId, $device);}
    public function id(): SessionId { return $this->id; }
    public function userId(): UserId { return $this->userId; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function lastActivityAt(): DateTimeImmutable { return $this->lastActivityAt; }
    public function isRevoked(): bool { return $this->revoked; }
    public function belongsTo(TenantId $tenantId, UserId $userId): bool
    { return $this->tenantId->equals($tenantId) && $this->userId->equals($userId);}
    public function updateActivity(): void
    { $this->lastActivityAt = new DateTimeImmutable();}
    public function revoke(): void
    { $this->revoked = true;}
    public function isIdle(int $idleTimeoutSeconds): bool
    { return time() - $this->lastActivityAt->getTimestamp() > $idleTimeoutSeconds;}
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
