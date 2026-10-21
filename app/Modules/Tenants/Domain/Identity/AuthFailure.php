<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Domain\Identity;

use RuntimeException;

final class AuthFailure extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function invalidCredentials(): self
    {
        return new self('INVALID_CREDENTIALS', 401, 'Credenciales inválidas.');
    }

    public static function invalidToken(): self
    {
        return new self('TOKEN_INVALID', 401, 'Token inválido o expirado.');
    }

    public static function expiredToken(): self
    {
        return new self('TOKEN_EXPIRED', 401, 'Token inválido o expirado.');
    }

    public static function missingToken(): self
    {
        return new self('TOKEN_MISSING', 401, 'Debe enviar un token Bearer.');
    }

    public static function issuerMismatch(): self
    {
        return new self('ISSUER_MISMATCH', 401, 'El emisor del token no es válido.');
    }

    public static function audienceMismatch(): self
    {
        return new self('AUDIENCE_MISMATCH', 401, 'La audiencia del token no es válida.');
    }

    public static function inactiveUser(): self
    {
        return new self('USER_INACTIVE', 401, 'El usuario no está activo.');
    }

    public static function userNotFound(): self
    {
        return new self('USER_NOT_FOUND', 401, 'El usuario no existe.');
    }

    public static function noActiveMembership(): self
    {
        return new self('NO_ACTIVE_MEMBERSHIP', 403, 'No hay una membresía activa para el hospital indicado.');
    }

    public static function noActiveRoles(): self
    {
        return new self('NO_ACTIVE_ROLES', 403, 'No hay roles activos canónicos para el hospital indicado.');
    }

    public static function rolesChanged(): self
    {
        return new self('ROLES_CHANGED', 401, 'Los roles cambiaron; vuelva a iniciar sesión.');
    }

    public static function sessionRevoked(): self
    {
        return new self('SESSION_REVOKED', 401, 'La sesión fue revocada.');
    }

    public static function tenantMismatch(): self
    {
        return new self('TENANT_MISMATCH', 403, 'El hospital indicado no coincide con el token.');
    }

    public static function tenantRequired(): self
    {
        return new self('TENANT_REQUIRED', 400, 'La cabecera X-Tenant-ID es obligatoria.');
    }

    public static function invalidRefreshToken(): self
    {
        return new self('REFRESH_TOKEN_INVALID', 401, 'Refresh token inválido o expirado.');
    }

    public static function refreshTokenReused(): self
    {
        return new self('REFRESH_TOKEN_REUSED', 401, 'Refresh token reutilizado; la familia de sesiones fue revocada.');
    }
}
