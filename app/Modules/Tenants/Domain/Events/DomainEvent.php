<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Events;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
/**
 * Evento de seguridad del módulo. Una sola clase (un archivo, autocargable por PSR-4)
 * con el nombre del evento como dato; el payload nunca debe llevar secretos.
 */
final class DomainEvent
{
    public const USER_CREATED = 'USER_CREATED';
    public const TENANT_CREATED = 'TENANT_CREATED';
    public const LOGIN_SUCCESS = 'LOGIN_SUCCESS';
    public const LOGIN_FAILED = 'LOGIN_FAILED';
    public const MFA_CHALLENGED = 'MFA_CHALLENGED';
    public const MFA_SUCCEEDED = 'MFA_SUCCEEDED';
    public const MFA_FAILED = 'MFA_FAILED';
    public const ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    public const SESSION_REVOKED = 'SESSION_REVOKED';
    public const TOKEN_REFRESHED = 'TOKEN_REFRESHED';
    public const REFRESH_REUSE_DETECTED = 'REFRESH_REUSE_DETECTED';
    public const CROSS_TENANT_ATTEMPT = 'CROSS_TENANT_ATTEMPT';
    public const TENANT_QUOTA_EXCEEDED = 'TENANT_QUOTA_EXCEEDED';
    public const TENANT_INACTIVE = 'TENANT_INACTIVE';
    private const SEVERITY = [
        self::ACCOUNT_LOCKED => 'HIGH',
        self::CROSS_TENANT_ATTEMPT => 'HIGH',
        self::REFRESH_REUSE_DETECTED => 'CRITICAL',];
    private const FORBIDDEN_KEYS = ['password', 'token', 'access_token', 'refresh_token', 'secret', 'totp_secret', 'code'];
    private string $eventId;
    private DateTimeImmutable $occurredAt;
    private string $eventName;
    private array $payload;
    public function __construct(string $eventName, array $payload)
    {
        if (!defined(self::class . '::' . $eventName)) {
            throw new InvalidArgumentException('Evento desconocido: ' . $eventName);}
        if (array_intersect(array_keys($payload), self::FORBIDDEN_KEYS) !== []) {
            throw new InvalidArgumentException('El payload de auditoría no puede contener secretos');}
        $this->eventId = Uuid::uuid4()->toString();
        $this->occurredAt = new DateTimeImmutable();
        $this->eventName = $eventName;
        $this->payload = $payload;}
    public function eventId(): string { return $this->eventId; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
    public function eventName(): string { return $this->eventName; }
    public function payload(): array { return $this->payload; }
    public function tenantId(): ?string { return $this->payload['tenant_id'] ?? null; }
    public function severity(): string { return self::SEVERITY[$this->eventName] ?? 'INFO'; }}
