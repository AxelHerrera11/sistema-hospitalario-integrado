<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Events;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
class SessionRevoked extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private string $reason;
    private int $revokedCount;
    private ?UserId $requesterId;
    public function __construct(
        UserId $userId,
        TenantId $tenantId,
        string $reason,
        int $revokedCount,
        ?UserId $requesterId = null
    ) {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->reason = $reason;
        $this->revokedCount = $revokedCount;
        $this->requesterId = $requesterId;}
    public function eventName(): string { return 'SESSION_REVOKED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'reason' => $this->reason,
            'revoked_count' => $this->revokedCount,
            'requester_id' => $this->requesterId?->value(),];}}
class TokenRefreshed extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private string $oldJti;
    private string $newJti;
    private DeviceFingerprint $device;
    public function __construct(
        UserId $userId,
        TenantId $tenantId,
        string $oldJti,
        string $newJti,
        DeviceFingerprint $device
    ) {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->oldJti = $oldJti;
        $this->newJti = $newJti;
        $this->device = $device;
    }
    public function eventName(): string { return 'TOKEN_REFRESHED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'old_jti' => $this->oldJti,
            'new_jti' => $this->newJti,
            'device' => $this->device->toArray(),];}}
class RefreshReuseDetected extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private DeviceFingerprint $device;
    private string $ip;
    public function __construct(
        UserId $userId,
        TenantId $tenantId,
        DeviceFingerprint $device,
        string $ip
    ) {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->device = $device;
        $this->ip = $ip;}
    public function eventName(): string { return 'REFRESH_REUSE_DETECTED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'device' => $this->device->toArray(),
            'ip' => $this->ip,
            'severity' => 'CRITICAL',];}}
class AccountLocked extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private string $lockType;
    private int $durationSeconds;
    private int $failedAttempts;
    public function __construct(
        UserId $userId,
        TenantId $tenantId,
        string $lockType,
        int $durationSeconds,
        int $failedAttempts
    ) {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->lockType = $lockType;
        $this->durationSeconds = $durationSeconds;
        $this->failedAttempts = $failedAttempts;}
    public function eventName(): string { return 'ACCOUNT_LOCKED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'lock_type' => $this->lockType,
            'duration_seconds' => $this->durationSeconds,
            'failed_attempts' => $this->failedAttempts,];}}
class CrossTenantAttempt extends DomainEvent
{
    private UserId $userId;
    private TenantId $sourceTenantId;
    private TenantId $targetTenantId;
    private string $ip;
    public function __construct(
        UserId $userId,
        TenantId $sourceTenantId,
        TenantId $targetTenantId,
        string $ip
    ) {
        parent::__construct();
        $this->userId = $userId;
        $this->sourceTenantId = $sourceTenantId;
        $this->targetTenantId = $targetTenantId;
        $this->ip = $ip;}
    public function eventName(): string { return 'CROSS_TENANT_ATTEMPT'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'source_tenant_id' => $this->sourceTenantId->value(),
            'target_tenant_id' => $this->targetTenantId->value(),
            'ip' => $this->ip,
            'severity' => 'HIGH',];}}
class TenantQuotaExceeded extends DomainEvent
{
    private TenantId $tenantId;
    private int $currentCount;
    private int $quota;
    public function __construct(TenantId $tenantId, int $currentCount, int $quota)
    {
        parent::__construct();
        $this->tenantId = $tenantId;
        $this->currentCount = $currentCount;
        $this->quota = $quota;}
    public function eventName(): string { return 'TENANT_QUOTA_EXCEEDED'; }
    public function payload(): array
    {
        return [
            'tenant_id' => $this->tenantId->value(),
            'current_count' => $this->currentCount,
            'quota' => $this->quota,];}}
class TenantInactive extends DomainEvent
{
    private TenantId $tenantId;
    private UserId $userId;
    private string $ip;
    public function __construct(TenantId $tenantId, UserId $userId, string $ip)
    {
        parent::__construct();
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->ip = $ip;}
    public function eventName(): string { return 'TENANT_INACTIVE'; }
    public function payload(): array
    {
        return [
            'tenant_id' => $this->tenantId->value(),
            'user_id' => $this->userId->value(),
            'ip' => $this->ip,];}}
