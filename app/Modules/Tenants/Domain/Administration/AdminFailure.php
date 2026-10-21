<?php

declare(strict_types=1);

namespace App\Modules\Tenants\Domain\Administration;

use RuntimeException;

final class AdminFailure extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function selfChange(): self
    {
        return new self('SELF_ROLE_CHANGE', 403, 'No puedes modificar tus propios roles.');
    }

    public static function superadminRequiresSuperadmin(): self
    {
        return new self(
            'SUPERADMIN_REQUIRES_SUPERADMIN',
            403,
            'Solo un superadministrador puede asignar o retirar el rol superadmin.',
        );
    }

    public static function unknownRole(): self
    {
        return new self('ROLE_INVALID', 422, 'El rol indicado no es canónico.');
    }

    public static function targetNotFound(): self
    {
        return new self('USER_NOT_FOUND', 404, 'Usuario no encontrado.');
    }

    public static function targetInactive(): self
    {
        return new self('USER_INACTIVE', 409, 'El usuario no está activo.');
    }

    public static function alreadyAssigned(): self
    {
        return new self('ROLE_ALREADY_ASSIGNED', 409, 'El usuario ya tiene ese rol activo en el hospital.');
    }

    public static function membershipNotFound(): self
    {
        return new self('MEMBERSHIP_NOT_FOUND', 404, 'El usuario no tiene ese rol activo en el hospital.');
    }

    public static function activeMembershipNotFound(): self
    {
        return new self('MEMBERSHIP_NOT_FOUND', 404, 'El usuario no tiene membresías activas en el hospital.');
    }

    public static function actorNotMember(): self
    {
        return new self('ACTOR_NO_ACTIVE_MEMBERSHIP', 403, 'No tienes una membresía activa en el hospital indicado.');
    }

    public static function sessionNotFound(): self
    {
        return new self('SESSION_NOT_FOUND', 404, 'Sesión activa no encontrada en el hospital indicado.');
    }

    public static function networkNotFound(): self
    {
        return new self('NETWORK_NOT_FOUND', 404, 'La red indicada no existe.');
    }

    public static function hospitalCodeConflict(): self
    {
        return new self('HOSPITAL_CODE_CONFLICT', 409, 'El código ya existe dentro de la red indicada.');
    }
}
