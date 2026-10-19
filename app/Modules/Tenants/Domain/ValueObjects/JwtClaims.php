<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
final class JwtClaims
{
    private string $subject;
    private array $roles;
    private string $tenantId;
    private string $sessionId;
    private string $jti;
    private string $deviceId;
    private int $issuedAt;
    private int $expiresAt;
    private int $notBefore;
    public function __construct(
        string $subject,
        array $roles,
        string $tenantId,
        string $sessionId,
        string $jti,
        string $deviceId,
        int $issuedAt,
        int $expiresAt,
        int $notBefore
) {
        if (empty($subject)) throw new InvalidArgumentException('Subject (sub) requerido');
        if (empty($roles)) throw new InvalidArgumentException('Roles requeridos');
        if (empty($tenantId)) throw new InvalidArgumentException('Tenant ID requerido');
        if (empty($sessionId)) throw new InvalidArgumentException('Session ID requerido');
        if (empty($jti)) throw new InvalidArgumentException('JTI requerido');
        if (empty($deviceId)) throw new InvalidArgumentException('Device ID requerido');
        if ($expiresAt <= $issuedAt) throw new InvalidArgumentException('ExpiresAt debe ser mayor que IssuedAt');
        if ($notBefore > $issuedAt) throw new InvalidArgumentException('NotBefore no puede ser mayor que IssuedAt');
        try {
            Uuid::isValid($subject) || throw new InvalidArgumentException('Subject debe ser UUID válido');
            Uuid::isValid($tenantId) || throw new InvalidArgumentException('TenantId debe ser UUID válido');
            Uuid::isValid($sessionId) || throw new InvalidArgumentException('SessionId debe ser UUID válido');
            Uuid::isValid($jti) || throw new InvalidArgumentException('JTI debe ser UUID válido');
            Uuid::isValid($deviceId) || throw new InvalidArgumentException('DeviceId debe ser UUID válido');
        } catch (UnsatisfiedDependencyException) {
            throw new InvalidArgumentException('UUID library no disponible');}
        $this->subject = $subject;
        $this->roles = $roles;
        $this->tenantId = $tenantId;
        $this->sessionId = $sessionId;
        $this->jti = $jti;
        $this->deviceId = $deviceId;
        $this->issuedAt = $issuedAt;
        $this->expiresAt = $expiresAt;
        $this->notBefore = $notBefore;}
    public function subject(): string { return $this->subject; }
    public function roles(): array { return $this->roles; }
    public function tenantId(): string { return $this->tenantId; }
    public function sessionId(): string { return $this->sessionId; }
    public function jti(): string { return $this->jti; }
    public function deviceId(): string { return $this->deviceId; }
    public function issuedAt(): int { return $this->issuedAt; }
    public function expiresAt(): int { return $this->expiresAt; }
    public function notBefore(): int { return $this->notBefore; }
    public function toArray(): array
    {
        return [
            'sub' => $this->subject,
            'roles' => $this->roles,
            'tenant_id' => $this->tenantId,
            'session_id' => $this->sessionId,
            'jti' => $this->jti,
            'device_id' => $this->deviceId,
            'iat' => $this->issuedAt,
            'exp' => $this->expiresAt,
            'nbf' => $this->notBefore,];}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['sub'],
            $data['roles'],
            $data['tenant_id'],
            $data['session_id'],
            $data['jti'],
            $data['device_id'],
            $data['iat'],
            $data['exp'],
            $data['nbf'] ?? $data['iat'],);}}
