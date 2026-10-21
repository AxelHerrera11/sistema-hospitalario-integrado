<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Entities;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
/** Desafío MFA pendiente: vence, admite pocos intentos y se consume una sola vez. */
final class MfaChallenge
{
    private string $id;
    private TenantId $tenantId;
    private UserId $userId;
    private DeviceFingerprint $device;
    private string $ip;
    private DateTimeImmutable $expiresAt;
    private int $failedAttempts = 0;
    private function __construct(TenantId $tenantId, UserId $userId, DeviceFingerprint $device, string $ip, int $ttlSeconds)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->device = $device;
        $this->ip = $ip;
        $this->expiresAt = (new DateTimeImmutable())->modify("+{$ttlSeconds} seconds");}
    public static function open(TenantId $tenantId, UserId $userId, DeviceFingerprint $device, string $ip, int $ttlSeconds = 300): self
    { return new self($tenantId, $userId, $device, $ip, $ttlSeconds);}
    public function id(): string { return $this->id; }
    public function tenantId(): TenantId { return $this->tenantId; }
    public function userId(): UserId { return $this->userId; }
    public function device(): DeviceFingerprint { return $this->device; }
    public function ip(): string { return $this->ip; }
    public function isExpired(): bool { return new DateTimeImmutable() >= $this->expiresAt; }
    /** @return int intentos fallidos acumulados */
    public function registerFailure(): int { return ++$this->failedAttempts; }}
