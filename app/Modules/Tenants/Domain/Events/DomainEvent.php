<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Events;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\ValueObjects\UserId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use DateTimeImmutable;
abstract class DomainEvent
{
    private string $eventId;
    private DateTimeImmutable $occurredAt;
    public function __construct()
    {
        $this->eventId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->occurredAt = new DateTimeImmutable();}
    public function eventId(): string { return $this->eventId; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
    abstract public function eventName(): string;
    abstract public function payload(): array;}
class UserCreated extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private string $email;
    private array $roles;
    private UserId $createdBy;
    public function __construct(
        UserId $userId,
        TenantId $tenantId,
        string $email,
        array $roles,
        UserId $createdBy
    ) {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->email = $email;
        $this->roles = $roles;
        $this->createdBy = $createdBy;}
    public function eventName(): string { return 'USER_CREATED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'email' => $this->email,
            'roles' => $this->roles,
            'created_by' => $this->createdBy->value(),];}}
class UserLoggedIn extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private array $roles;
    private bool $mfaUsed;
    private DeviceFingerprint $device;
    private string $ip;
    private string $sessionId;
    public function __construct(
        UserId $userId,
        TenantId $tenantId,
        array $roles,
        bool $mfaUsed,
        DeviceFingerprint $device,
        string $ip,
        string $sessionId
    ) {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->roles = $roles;
        $this->mfaUsed = $mfaUsed;
        $this->device = $device;
        $this->ip = $ip;
        $this->sessionId = $sessionId;}
    public function eventName(): string { return 'LOGIN_SUCCESS'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'roles' => $this->roles,
            'mfa_used' => $this->mfaUsed,
            'device' => $this->device->toArray(),
            'ip' => $this->ip,
            'session_id' => $this->sessionId,];}}
class UserLoginFailed extends DomainEvent
{
    private string $email;
    private TenantId $tenantId;
    private string $reason;
    private string $ip;
    public function __construct(string $email, TenantId $tenantId, string $reason, string $ip)
    {
        parent::__construct();
        $this->email = $email;
        $this->tenantId = $tenantId;
        $this->reason = $reason;
        $this->ip = $ip;}
    public function eventName(): string { return 'LOGIN_FAILED'; }
    public function payload(): array
    {
        return [
            'email' => $this->email,
            'tenant_id' => $this->tenantId->value(),
            'reason' => $this->reason,
            'ip' => $this->ip,];}}
class MfaChallenged extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private string $method;
    private string $challengeId;
    public function __construct(UserId $userId, TenantId $tenantId, string $method, string $challengeId)
    {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->method = $method;
        $this->challengeId = $challengeId;}
    public function eventName(): string { return 'MFA_CHALLENGED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'method' => $this->method,
            'challenge_id' => $this->challengeId,];}}
class MfaSucceeded extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private string $method;
    public function __construct(UserId $userId, TenantId $tenantId, string $method)
    {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->method = $method;}
    public function eventName(): string { return 'MFA_SUCCEEDED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'method' => $this->method,];}}
class MfaFailed extends DomainEvent
{
    private UserId $userId;
    private TenantId $tenantId;
    private string $method;
    public function __construct(UserId $userId, TenantId $tenantId, string $method)
    {
        parent::__construct();
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->method = $method;}
    public function eventName(): string { return 'MFA_FAILED'; }
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'tenant_id' => $this->tenantId->value(),
            'method' => $this->method,];}}
