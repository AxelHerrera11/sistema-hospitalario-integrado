<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
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
        if (empty($roles)) throw new InvalidArgumentException('Roles requeridos');
        if (empty($deviceId)) throw new InvalidArgumentException('Device ID requerido');
        if ($expiresAt <= $issuedAt) throw new InvalidArgumentException('ExpiresAt debe ser mayor que IssuedAt');
        if ($notBefore > $issuedAt) throw new InvalidArgumentException('NotBefore no puede ser mayor que IssuedAt');
        // El device_id lo envía el cliente (X-Device-ID) y no tiene por qué ser UUID.
        foreach (['Subject' => $subject, 'TenantId' => $tenantId, 'SessionId' => $sessionId, 'JTI' => $jti] as $name => $value) {
            if (!Uuid::isValid($value)) throw new InvalidArgumentException($name . ' debe ser UUID válido');}
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
    public function hasRole(string $role): bool { return in_array($role, $this->roles, true); }
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
            'nbf' => $this->notBefore,];}}
